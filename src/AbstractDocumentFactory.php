<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Documents\Models\DocumentInterface;
use RZ\Roadiz\Documents\Models\FileHashInterface;
use RZ\Roadiz\Documents\Models\FolderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Create documents from UploadedFile.
 *
 * Factory methods do not flush, only persist in order to use it in loops.
 */
abstract class AbstractDocumentFactory
{
    private LoggerInterface $logger;
    private ?File $file = null;
    private ?FolderInterface $folder = null;
    private FilesystemOperator $documentsStorage;

    public function __construct(
        FilesystemOperator $documentsStorage,
        ?LoggerInterface $logger = null
    ) {
        $this->documentsStorage = $documentsStorage;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        if (null === $this->file) {
            throw new \BadMethodCallException('File should be defined before using it.');
        }
        return $this->file;
    }

    /**
     * @param  File $file
     * @return $this
     */
    public function setFile(File $file): static
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return FolderInterface|null
     */
    public function getFolder(): ?FolderInterface
    {
        return $this->folder;
    }

    /**
     * @param FolderInterface|null $folder
     * @return $this
     */
    public function setFolder(?FolderInterface $folder = null): static
    {
        $this->folder = $folder;
        return $this;
    }

    /**
     * Special case for SVG without XML statement.
     *
     * @param DocumentInterface $document
     */
    protected function parseSvgMimeType(DocumentInterface $document): void
    {
        if (
            ($document->getMimeType() === 'text/plain' || $document->getMimeType() === 'text/html') &&
            preg_match('#\.svg$#', $document->getFilename())
        ) {
            $this->logger->debug('Uploaded a SVG without xml declaration. Presuming it’s a valid SVG file.');
            $document->setMimeType('image/svg+xml');
        }
    }

    /**
     * @return DocumentInterface
     */
    abstract protected function createDocument(): DocumentInterface;

    /**
     * @param DocumentInterface $document
     */
    abstract protected function persistDocument(DocumentInterface $document): void;

    protected function getHashAlgorithm(): string
    {
        return 'sha256';
    }

    /**
     * Create a document from UploadedFile, Be careful, this method does not flush, only
     * persists current Document.
     *
     * @param bool $allowEmpty
     *
     * @return null|DocumentInterface
     * @throws FilesystemException
     */
    public function getDocument(bool $allowEmpty = false): ?DocumentInterface
    {
        $document = $this->createDocument();

        if ($allowEmpty === false) {
            // Getter throw exception on null file
            $file = $this->getFile();
        } else {
            $file = $this->file;
        }

        if (null !== $file) {
            if ($file instanceof UploadedFile && !$file->isValid()) {
                return null;
            }
            $document->setFilename($this->getFileName());
            if ($file instanceof UploadedFile) {
                $document->setMimeType($file->getClientMimeType());
            } else {
                $document->setMimeType($file->getMimeType() ?? '');
            }
            $this->parseSvgMimeType($document);

            if (
                $document instanceof FileHashInterface &&
                false !== $fileHash = hash_file($this->getHashAlgorithm(), $file->getPathname())
            ) {
                $document->setFileHash($fileHash);
                $document->setFileHashAlgorithm($this->getHashAlgorithm());
            }

            $this->moveFile($file, $document);
        }

        $this->persistDocument($document);

        if (null !== $this->folder) {
            $document->addFolder($this->folder);
            $this->folder->addDocument($document);
        }

        return $document;
    }

    /**
     * Updates a document from UploadedFile, Be careful, this method does not flush.
     *
     * @param DocumentInterface $document
     * @return DocumentInterface
     * @throws FilesystemException
     */
    public function updateDocument(DocumentInterface $document): DocumentInterface
    {
        $file = $this->getFile();

        if (
            $file instanceof UploadedFile
            && !$file->isValid()
        ) {
            return $document;
        }

        if ($document->isLocal()) {
            /*
             * In case file already exists
             */
            if ($this->documentsStorage->fileExists($document->getMountPath())) {
                $this->documentsStorage->delete($document->getMountPath());
            }
        }

        if (DownloadedFile::sanitizeFilename($this->getFileName()) == $document->getFilename()) {
            $previousFolder = $document->getMountFolderPath();

            if ($this->documentsStorage->directoryExists($previousFolder)) {
                $hasFiles = \count($this->documentsStorage->listContents($previousFolder)->toArray()) > 0;
                // Remove previous folder if it's empty
                if (!$hasFiles) {
                    $this->documentsStorage->deleteDirectory($previousFolder);
                }
            }

            $document->setFolder(substr(hash("crc32b", date('YmdHi')), 0, 12));
        }

        $document->setFilename($this->getFileName());
        if ($file instanceof UploadedFile) {
            $document->setMimeType($file->getClientMimeType());
        } else {
            $document->setMimeType($file->getMimeType() ?? '');
        }
        $this->parseSvgMimeType($document);
        $this->moveFile($file, $document);

        return $document;
    }

    /**
     * @param File $localFile
     * @param DocumentInterface $document
     * @return void
     * @throws FilesystemException
     */
    public function moveFile(File $localFile, DocumentInterface $document): void
    {
        if (null !== $document->getMountPath()) {
            $stream = fopen($localFile->getPathname(), 'r');
            $this->documentsStorage->writeStream(
                $document->getMountPath(),
                $stream
            );
            if (is_resource($stream)) {
                fclose($stream);
            }
            (new Filesystem())->remove($localFile);
        }
    }

    /**
     * @return string
     */
    protected function getFileName(): string
    {
        $file = $this->getFile();

        if ($file instanceof UploadedFile) {
            $fileName = $file->getClientOriginalName();
        } elseif (
            $file instanceof DownloadedFile
            && $file->getOriginalFilename() !== null
            && $file->getOriginalFilename() !== ''
        ) {
            $fileName = $file->getOriginalFilename();
        } else {
            $fileName = $file->getFilename();
        }

        return $fileName;
    }

    /**
     * Create a Document from an external URL.
     *
     * @param string $downloadUrl
     *
     * @return DocumentInterface|null
     * @throws FilesystemException
     */
    public function getDocumentFromUrl(string $downloadUrl): ?DocumentInterface
    {
        $downloadedFile = DownloadedFile::fromUrl($downloadUrl);
        if (null !== $downloadedFile) {
            return $this->setFile($downloadedFile)->getDocument();
        }
        return null;
    }
}
