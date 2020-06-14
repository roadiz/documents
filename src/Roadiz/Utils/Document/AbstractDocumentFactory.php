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
    /**
     * @var File
     */
    private $file;
    /**
     * @var FolderInterface|null
     */
    private $folder;
    /**
     * @var LoggerInterface|null
     */
    private $logger;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;
    /**
     * @var Packages
     */
    private $packages;

    /**
     * AbstractDocumentFactory constructor.
     *
     * @param EntityManagerInterface   $em
     * @param EventDispatcherInterface $dispatcher
     * @param Packages                 $packages
     * @param LoggerInterface|null     $logger
     */
    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $dispatcher,
        Packages $packages,
        LoggerInterface $logger = null
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->dispatcher = $dispatcher;
        $this->packages = $packages;

        if (null === $this->logger) {
            $this->logger = new NullLogger();
        }
    }

    /**
     * @return File
     */
    public function getFile()
    {
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
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param FolderInterface|null $folder
     * @return AbstractDocumentFactory
     */
    public function setFolder(FolderInterface $folder = null)
    {
        $this->folder = $folder;
        return $this;
    }

    /**
     * Special case for SVG without XML statement.
     *
     * @param DocumentInterface $document
     */
    protected function parseSvgMimeType(DocumentInterface $document)
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
    abstract protected function createDocument();

    /**
     * Create a document from UploadedFile, Be careful, this method does not flush, only
     * persists current Document.
     *
     * @param bool $allowEmpty
     *
     * @return null|DocumentInterface
     */
    public function getDocument($allowEmpty = false)
    {
        $document = $this->createDocument();

        if ($allowEmpty === false && null === $this->file) {
            throw new \InvalidArgumentException('File must be set before getting document.');
        }

        if (null !== $this->file) {
            if ($this->file instanceof UploadedFile && !$this->file->isValid()) {
                return null;
            }
            $document->setFilename($this->getFileName());
            $document->setMimeType($this->file->getMimeType() ?? '');
            $this->parseSvgMimeType($document);
            $this->file->move(
                $this->packages->getDocumentFolderPath($document),
                $document->getFilename()
            );
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
    protected function dispatchEvents(DocumentInterface $document)
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
    public function updateDocument(DocumentInterface $document)
    {
        if (null === $this->file) {
            throw new \InvalidArgumentException('File must be set before getting document.');
        }

        $fs = new Filesystem();

        if ($this->file instanceof UploadedFile &&
            !$this->file->isValid()) {
            return $document;
        }

        $documentPath = $this->packages->getDocumentFilePath($document);

        /*
         * In case file already exists
         */
        if ($fs->exists($documentPath)) {
            $fs->remove($documentPath);
        }

        if (StringHandler::cleanForFilename($this->getFileName()) == $document->getFilename()) {
            $finder = new Finder();
            $previousFolder = $this->packages->getDocumentFolderPath($document);

            if ($fs->exists($previousFolder)) {
                $finder->files()->in($previousFolder);
                // Remove Precious folder if it's empty
                if ($finder->count() == 0) {
                    $fs->remove($previousFolder);
                }
            }

            $document->setFolder(substr(hash("crc32b", date('YmdHi')), 0, 12));
        }

        $document->setFilename($this->getFileName());
        $document->setMimeType($this->file->getMimeType() ?? '');
        $this->parseSvgMimeType($document);

        $this->file->move(
            $this->packages->getDocumentFolderPath($document),
            $document->getFilename()
        );

        $this->dispatchEvents($document);

        return $document;
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        if (null === $this->file) {
            throw new \InvalidArgumentException('File must be set before getting its fileName.');
        }

        if ($this->file instanceof UploadedFile) {
            $fileName = $this->file->getClientOriginalName();
        } elseif ($this->file instanceof DownloadedFile && $this->file->getOriginalFilename() !== '') {
            $fileName = $this->file->getOriginalFilename();
        } else {
            $fileName = $this->file->getFilename();
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
