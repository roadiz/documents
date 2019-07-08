<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file DocumentFactory.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Utils\Document;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Events\DocumentEvents;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
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
     * @var FolderInterface
     */
    private $folder;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManager
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
     * DocumentFactory constructor.
     *
     * @param EntityManager $em
     * @param EventDispatcherInterface $dispatcher
     * @param Packages $packages
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager $em,
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
     * @return FolderInterface
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param FolderInterface $folder
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
            $this->logger->debug('Uploaded a SVG without xml declaration. Presuming it’s a valid SVG file.');
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
            $document->setMimeType($this->file->getMimeType());
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
                DocumentEvents::DOCUMENT_IMAGE_UPLOADED,
                new FilterDocumentEvent($document)
            );
        }

        if ($document->getMimeType() == 'image/svg+xml') {
            $this->dispatcher->dispatch(
                DocumentEvents::DOCUMENT_SVG_UPLOADED,
                new FilterDocumentEvent($document)
            );
        }
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
        $document->setMimeType($this->file->getMimeType());
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
     */
    public function downloadFileFromUrl(string $url, string $thumbnailName): ?DownloadedFile
    {
        try {
            $distantHandle = fopen($url, 'r');
            if (false === $distantHandle) {
                return null;
            }
            $original = \GuzzleHttp\Psr7\stream_for($distantHandle);
            $tmpFile = tempnam(sys_get_temp_dir(), $thumbnailName);
            $handle = fopen($tmpFile, 'w');
            $local = \GuzzleHttp\Psr7\stream_for($handle);
            $local->write($original->getContents());
            $local->close();

            $file = new DownloadedFile($tmpFile);
            $file->setOriginalFilename($thumbnailName);

            if ($file->isReadable() &&
                filesize($file->getPathname()) > 0) {
                return $file;
            }
        } catch (RequestException $e) {
            return null;
        }
    }
}
