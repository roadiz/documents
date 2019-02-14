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
 * @file DocumentLifeCycleSubscriber.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\FileAwareInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Handle file management on documents lifecycle events.
 *
 * @package Roadiz\Core\Events
 */
class DocumentLifeCycleSubscriber implements EventSubscriber
{
    /**
     * @var FileAwareInterface
     */
    private $fileAware;

    /**
     * DocumentLifeCycleSubscriber constructor.
     *
     * @param FileAwareInterface $fileAware
     */
    public function __construct(FileAwareInterface $fileAware)
    {
        $this->fileAware = $fileAware;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::postRemove,
            Events::preUpdate,
        );
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $document = $args->getObject();
        if ($document instanceof DocumentInterface && $args->hasChangedField('filename')) {
            $fs = new Filesystem();
            $oldPath = $this->getDocumentPathForFilename($document, $args->getOldValue('filename'));
            $newPath = $this->getDocumentPathForFilename($document, $args->getNewValue('filename'));

            if ($oldPath !== $newPath) {
                if ($fs->exists($oldPath) && is_file($oldPath) && !$fs->exists($newPath)) {
                    /*
                     * Only perform IO rename if old file exists and new path is free.
                     */
                    $fs->rename($oldPath, $newPath);
                }
            }
        }
    }

    /**
     * Unlink file after document has been deleted.
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $document = $args->getObject();
        if ($document instanceof DocumentInterface) {
            $fileSystem = new Filesystem();
            $document->setRawDocument(null);
            $documentPath = $this->getDocumentPath($document);

            if ($document->getFilename() !== '') {
                if ($fileSystem->exists($documentPath) && is_file($documentPath)) {
                    $fileSystem->remove($documentPath);
                }
                $this->cleanFileDirectory($document);
            }
        }
    }

    /**
     * Remove document directory if there is no other file in it.
     *
     * @param DocumentInterface $document
     * @return bool
     */
    protected function cleanFileDirectory(DocumentInterface $document)
    {
        $documentFolderPath = $this->getDocumentFolderPath($document);
        $fileSystem = new Filesystem();

        if ($fileSystem->exists($documentFolderPath)) {
            $isDirEmpty = !(new \FilesystemIterator($documentFolderPath))->valid();
            if ($isDirEmpty) {
                $fileSystem->remove($documentFolderPath);
            }
        }

        return false;
    }

    /**
     * @return null|string
     */
    protected function getDocumentRelativePathForFilename(DocumentInterface $document, $filename)
    {
        return $document->getFolder() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    protected function getDocumentPathForFilename(DocumentInterface $document, $filename)
    {
        if ($document->isPrivate()) {
            return $this->fileAware->getPrivateFilesPath() .
                DIRECTORY_SEPARATOR .
                $this->getDocumentRelativePathForFilename($document, $filename);
        }
        return $this->fileAware->getPublicFilesPath() .
            DIRECTORY_SEPARATOR .
            $this->getDocumentRelativePathForFilename($document, $filename);
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    protected function getDocumentPath(DocumentInterface $document)
    {
        if ($document->isPrivate()) {
            return $this->fileAware->getPrivateFilesPath() . DIRECTORY_SEPARATOR . $document->getRelativePath();
        }
        return $this->fileAware->getPublicFilesPath() . DIRECTORY_SEPARATOR . $document->getRelativePath();
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    protected function getDocumentFolderPath(DocumentInterface $document)
    {
        if ($document->isPrivate()) {
            return $this->fileAware->getPrivateFilesPath() . DIRECTORY_SEPARATOR . $document->getFolder();
        }
        return $this->fileAware->getPublicFilesPath() . DIRECTORY_SEPARATOR . $document->getFolder();
    }
}
