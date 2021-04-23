<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Document;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Events\DocumentFileUploadedEvent;
use RZ\Roadiz\Core\Events\DocumentImageUploadedEvent;
use RZ\Roadiz\Core\Events\DocumentSvgUploadedEvent;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\FolderInterface;
use RZ\Roadiz\Document\DownloadedFile;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Create documents from UploadedFile.
 *
 * Factory methods do not flush, only persist in order to use it in loops.
 *
 * @package RZ\Roadiz\Utils\Document
 */
abstract class AbstractDocumentFactory
{
    private EntityManagerInterface $em;
    private EventDispatcherInterface $dispatcher;
    private Packages $packages;
    private LoggerInterface $logger;
    private ?File $file = null;
    private ?FolderInterface $folder = null;

    /**
     * @param EntityManagerInterface   $em
     * @param EventDispatcherInterface $dispatcher
     * @param Packages                 $packages
     * @param LoggerInterface|null     $logger
     */
    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        Packages $packages,
        ?LoggerInterface $logger = null
    ) {
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->packages = $packages;

        if (null === $logger) {
            $this->logger = new NullLogger();
        } else {
            $this->logger = $logger;
        }
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
     * @param File $file
     * @return AbstractDocumentFactory
     */
    public function setFile(File $file)
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
     * @return AbstractDocumentFactory
     */
    public function setFolder(?FolderInterface $folder = null)
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
        if (($document->getMimeType() == "text/plain" ||
                $document->getMimeType() == 'text/html') &&
                preg_match('#\.svg$#', $document->getFilename())) {
            if (null !== $this->logger) {
                $this->logger->debug('Uploaded a SVG without xml declaration. Presuming it’s a valid SVG file.');
            }
            $document->setMimeType('image/svg+xml');
        }
    }

    /**
     * @return DocumentInterface
     */
    abstract protected function createDocument(): DocumentInterface;

    /**
     * Create a document from UploadedFile, Be careful, this method does not flush, only
     * persists current Document.
     *
     * @param bool $allowEmpty
     *
     * @return null|DocumentInterface
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
                $document->setMimeType($file->getClientMimeType() ?? '');
            } else {
                $document->setMimeType($file->getMimeType() ?? '');
            }
            $this->parseSvgMimeType($document);
            if ($document->getFilename() !== '') {
                $file->move(
                    $this->packages->getDocumentFolderPath($document),
                    $document->getFilename()
                );
            }
        }

        $this->em->persist($document);

        if (null !== $this->folder) {
            $document->addFolder($this->folder);
            $this->folder->addDocument($document);
        }

        $this->dispatchEvents($document);

        return $document;
    }

    /**
     * @param DocumentInterface $document
     */
    protected function dispatchEvents(DocumentInterface $document): void
    {
        if ($document->isImage()) {
            $this->dispatcher->dispatch(
                new DocumentImageUploadedEvent($document)
            );
        }

        if ($document->getMimeType() == 'image/svg+xml') {
            $this->dispatcher->dispatch(
                new DocumentSvgUploadedEvent($document)
            );
        }

        $this->dispatcher->dispatch(
            new DocumentFileUploadedEvent($document)
        );
    }

    /**
     * Updates a document from UploadedFile, Be careful, this method does not flush.
     *
     * @param DocumentInterface $document
     * @return DocumentInterface
     */
    public function updateDocument(DocumentInterface $document): DocumentInterface
    {
        $file = $this->getFile();
        $fs = new Filesystem();

        if ($file instanceof UploadedFile &&
            !$file->isValid()) {
            return $document;
        }

        if ($document->isLocal()) {
            $documentPath = $this->packages->getDocumentFilePath($document);

            /*
             * In case file already exists
             */
            if ($fs->exists($documentPath)) {
                $fs->remove($documentPath);
            }
        }

        if (StringHandler::cleanForFilename($this->getFileName()) == $document->getFilename()) {
            $finder = new Finder();
            $previousFolder = $this->packages->getDocumentFolderPath($document);

            if ($fs->exists($previousFolder)) {
                $finder->files()->in($previousFolder);
                // Remove previous folder if it's empty
                if ($finder->count() == 0) {
                    $fs->remove($previousFolder);
                }
            }

            $document->setFolder(substr(hash("crc32b", date('YmdHi')), 0, 12));
        }

        $document->setFilename($this->getFileName());
        if ($file instanceof UploadedFile) {
            $document->setMimeType($file->getClientMimeType() ?? '');
        } else {
            $document->setMimeType($file->getMimeType() ?? '');
        }
        $this->parseSvgMimeType($document);

        $file->move(
            $this->packages->getDocumentFolderPath($document),
            $document->getFilename()
        );

        $this->dispatchEvents($document);

        return $document;
    }

    /**
     * @return string
     */
    protected function getFileName(): string
    {
        $file = $this->getFile();

        if ($file instanceof UploadedFile) {
            $fileName = $file->getClientOriginalName();
        } elseif ($file instanceof DownloadedFile &&
            $file->getOriginalFilename() !== null &&
            $file->getOriginalFilename() !== ''
        ) {
            $fileName = $file->getOriginalFilename();
        } else {
            $fileName = $file->getFilename();
        }

        return $fileName;
    }

    /**
     * @param string $url
     * @param string $thumbnailName
     *
     * @return DownloadedFile
     * @deprecated Use DownloadedFile::fromUrl($url);
     */
    public function downloadFileFromUrl(string $url, string $thumbnailName): ?DownloadedFile
    {
        @trigger_error(
            'AbstractDocumentFactory::downloadFileFromUrl method is deprecated.' .
                ' Use '.DownloadedFile::class.'::fromUrl($url)',
            E_USER_DEPRECATED
        );
        return DownloadedFile::fromUrl($url, $thumbnailName);
    }

    /**
     * Create a Document from an external URL.
     *
     * @param string $downloadUrl
     *
     * @return DocumentInterface|null
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
