<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\FileAwareInterface;
use Symfony\Component\Filesystem\Filesystem;

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

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args): void
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
        if ($document instanceof DocumentInterface && $args->hasChangedField('private')) {
            if ($document->isPrivate() === true) {
                $this->makePrivate($document, $args);
            } else {
                $this->makePublic($document, $args);
            }
        }
    }

    /**
     * @param DocumentInterface  $document
     * @param PreUpdateEventArgs $args
     */
    protected function makePublic(DocumentInterface $document, PreUpdateEventArgs $args): void
    {
        $documentPublicPath = $this->getDocumentPublicPath($document);
        $documentPrivatePath = $this->getDocumentPrivatePath($document);

        $fs = new Filesystem();

        if ($fs->exists($documentPrivatePath)) {
            /*
             * Create destination folder if not exist
             */
            if (!$fs->exists(dirname($documentPublicPath))) {
                $fs->mkdir(dirname($documentPublicPath));
            }

            $fs->rename(
                $documentPrivatePath,
                $documentPublicPath
            );
            $this->cleanFileDirectory($this->getDocumentPrivateFolderPath($document));
        }
    }

    /**
     * @param DocumentInterface  $document
     * @param PreUpdateEventArgs $args
     */
    protected function makePrivate(DocumentInterface $document, PreUpdateEventArgs $args): void
    {
        $documentPublicPath = $this->getDocumentPublicPath($document);
        $documentPrivatePath = $this->getDocumentPrivatePath($document);

        $fs = new Filesystem();
        if ($fs->exists($documentPublicPath)) {
            /*
             * Create destination folder if not exist
             */
            if (!$fs->exists(dirname($documentPrivatePath))) {
                $fs->mkdir(dirname($documentPrivatePath));
            }
            $fs->rename(
                $documentPublicPath,
                $documentPrivatePath
            );
            $this->cleanFileDirectory($this->getDocumentPublicFolderPath($document));
        }
    }

    /**
     * Unlink file after document has been deleted.
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args): void
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
                $this->cleanFileDirectory($this->getDocumentFolderPath($document));
            }
        }
    }

    /**
     * Remove document directory if there is no other file in it.
     *
     * @param string $documentFolderPath
     * @return bool
     */
    protected function cleanFileDirectory(string $documentFolderPath)
    {
        $fileSystem = new Filesystem();

        if ($fileSystem->exists($documentFolderPath) && is_dir($documentFolderPath)) {
            $isDirEmpty = !(new \FilesystemIterator($documentFolderPath))->valid();
            if ($isDirEmpty) {
                $fileSystem->remove($documentFolderPath);
            }
        }

        return false;
    }

    /**
     * @param DocumentInterface $document
     * @param string $filename
     *
     * @return null|string
     */
    protected function getDocumentRelativePathForFilename(DocumentInterface $document, $filename)
    {
        return $document->getFolder() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @param DocumentInterface $document
     * @param string $filename
     *
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
            return $this->getDocumentPrivatePath($document);
        }
        return $this->getDocumentPublicPath($document);
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    protected function getDocumentPublicPath(DocumentInterface $document)
    {
        return $this->fileAware->getPublicFilesPath() . DIRECTORY_SEPARATOR . $document->getRelativePath();
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    protected function getDocumentPrivatePath(DocumentInterface $document)
    {
        return $this->fileAware->getPrivateFilesPath() . DIRECTORY_SEPARATOR . $document->getRelativePath();
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    protected function getDocumentFolderPath(DocumentInterface $document)
    {
        if ($document->isPrivate()) {
            return $this->getDocumentPrivateFolderPath($document);
        }
        return $this->getDocumentPublicFolderPath($document);
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    protected function getDocumentPublicFolderPath(DocumentInterface $document)
    {
        return $this->fileAware->getPublicFilesPath() . DIRECTORY_SEPARATOR . $document->getFolder();
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    protected function getDocumentPrivateFolderPath(DocumentInterface $document)
    {
        return $this->fileAware->getPrivateFilesPath() . DIRECTORY_SEPARATOR . $document->getFolder();
    }
}
