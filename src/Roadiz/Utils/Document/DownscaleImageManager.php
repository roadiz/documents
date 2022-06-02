<?php

declare(strict_types=1);

namespace RZ\Roadiz\Utils\Document;

use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Constraint;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\FileHashInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;

class DownscaleImageManager
{
    protected EntityManagerInterface $em;
    protected Packages $packages;
    protected ?LoggerInterface $logger;
    protected int $maxPixelSize = 0;
    protected string $rawImageSuffix = ".raw";
    protected ImageManager $manager;

    /**
     * @param EntityManagerInterface $em
     * @param Packages               $packages
     * @param LoggerInterface|null   $logger
     * @param string                 $imageDriver
     * @param int                    $maxPixelSize
     * @param string                 $rawImageSuffix
     */
    public function __construct(
        EntityManagerInterface $em,
        Packages $packages,
        ?LoggerInterface $logger = null,
        string $imageDriver = 'gd',
        int $maxPixelSize = 0,
        string $rawImageSuffix = ".raw"
    ) {
        $this->maxPixelSize = $maxPixelSize;
        $this->rawImageSuffix = $rawImageSuffix;
        $this->em = $em;
        $this->logger = $logger;
        $this->manager = new ImageManager(['driver' => $imageDriver]);
        $this->packages = $packages;
    }

    /**
     * Downscale document if needed, overriding raw document.
     *
     * @param DocumentInterface|null $document
     */
    public function processAndOverrideDocument(?DocumentInterface $document = null): void
    {
        if (null !== $document && $document->isLocal() && $this->maxPixelSize > 0) {
            $rawDocumentFilePath = $this->packages->getDocumentFilePath($document);
            $processImage = $this->getDownscaledImage($this->manager->make($rawDocumentFilePath));
            if (false !== $processImage) {
                if (
                    false !== $this->createDocumentFromImage($document, $processImage)
                    && null !== $this->logger
                ) {
                    $this->logger->info(
                        'Document has been downscaled.',
                        [
                        'path' => $rawDocumentFilePath
                        ]
                    );
                }
            }
        }
    }

    /**
     * Downscale document if needed, keeping existing raw document.
     *
     * @param DocumentInterface|null $document
     */
    public function processDocumentFromExistingRaw(?DocumentInterface $document = null): void
    {
        if (null !== $document && $document->isLocal() && $this->maxPixelSize > 0) {
            if (null !== $document->getRawDocument() && $document->getRawDocument()->isLocal()) {
                $rawDocumentFile = $this->packages->getDocumentFilePath($document->getRawDocument());
            } else {
                $rawDocumentFile = $this->packages->getDocumentFilePath($document);
            }

            if (false !== $processImage = $this->getDownscaledImage($this->manager->make($rawDocumentFile))) {
                if (
                    false !== $this->createDocumentFromImage($document, $processImage, true)
                    && null !== $this->logger
                ) {
                    $this->logger->info('Document has been downscaled.', ['path' => $rawDocumentFile]);
                }
            }
        }
    }

    /**
     * Get downscaled image if size is higher than limit,
     * returns original image if lower or if image is a GIF.
     *
     * @param  Image $processImage
     * @return Image|null
     */
    protected function getDownscaledImage(Image $processImage): ?Image
    {
        if (
            $processImage->mime() != 'image/gif'
            && ($processImage->width() > $this->maxPixelSize || $processImage->height() > $this->maxPixelSize)
        ) {
            // prevent possible upsizing
            $processImage->resize(
                $this->maxPixelSize,
                $this->maxPixelSize,
                function (Constraint $constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                }
            );
            return $processImage;
        } else {
            return null;
        }
    }

    /**
     * @param DocumentInterface $document
     * @param string $documentPath
     * @return void
     */
    protected function updateDocumentFileHash(DocumentInterface $document, string $documentPath): void
    {
        /*
         * We need to re-hash file after being downscaled
         */
        if (
            $document instanceof FileHashInterface &&
            null !== $document->getFileHashAlgorithm() &&
            false !== $fileHash = hash_file($document->getFileHashAlgorithm(), $documentPath)
        ) {
            $document->setFileHash($fileHash);
        }
    }

    /**
     * @param  DocumentInterface $originalDocument
     * @param  Image|null        $processImage
     * @param  bool          $keepExistingRaw
     * @return DocumentInterface|bool Return new Document or FALSE
     */
    protected function createDocumentFromImage(
        DocumentInterface $originalDocument,
        Image $processImage = null,
        bool $keepExistingRaw = false
    ) {
        $fs = new Filesystem();

        if (false === $keepExistingRaw && null !== $formerRawDoc = $originalDocument->getRawDocument()) {
            /*
             * When document already exists with a raw doc reference.
             * We have to delete former raw document before creating a new one.
             * Keeping the same document to preserve existing relationships!!
             */
            $originalDocument->setRawDocument(null);
            /*
             * Make sure to disconnect raw document before removing it
             * not to trigger Cascade deleting.
             */
            $this->em->flush();
            $this->em->remove($formerRawDoc);
            $this->em->flush();
        }

        if (null === $originalDocument->getRawDocument() || $keepExistingRaw === false) {
            /*
             * We clone it to host raw document.
             * Keeping the same document to preserve existing relationships!!
             *
             * Get every data from raw document.
             */
            if (null !== $processImage) {
                $rawDocument = clone $originalDocument;
                $rawDocumentName = preg_replace(
                    '#\.(jpe?g|gif|tiff?|png|psd|webp)$#',
                    $this->rawImageSuffix . '.$1',
                    $originalDocument->getFilename()
                );
                if (null === $rawDocumentName) {
                    throw new \InvalidArgumentException('Raw document filename cannot be null');
                }
                $rawDocument->setFilename($rawDocumentName);

                $originalDocumentPath = $this->packages->getDocumentFilePath($originalDocument);
                $rawDocumentPath = $this->packages->getDocumentFilePath($rawDocument);

                if (
                    $fs->exists($originalDocumentPath)
                    && !$fs->exists($rawDocumentPath)
                ) {
                    /*
                     * Original document path becomes raw document path. Rename it.
                     */
                    $fs->rename($originalDocumentPath, $rawDocumentPath);
                    /*
                     * Then save downscaled image as original document path.
                     */
                    $processImage->save($originalDocumentPath, 100);
                    $originalDocument->setRawDocument($rawDocument);

                    /*
                     * We need to re-hash file after being downscaled
                     */
                    $this->updateDocumentFileHash($originalDocument, $originalDocumentPath);

                    $rawDocument->setRaw(true);

                    $this->em->persist($rawDocument);
                    $this->em->flush();

                    return $originalDocument;
                } else {
                    return false;
                }
            } else {
                return $originalDocument;
            }
        } elseif (null !== $processImage) {
            /*
             * New downscale document has been generated, we keep existing RAW document
             * but we override downscaled file with the new one.
             */
            $originalDocumentPath = $this->packages->getDocumentFilePath($originalDocument);
            /*
             * Remove existing downscaled document.
             */
            $fs->remove($originalDocumentPath);
            /*
             * Then save downscaled image as original document path.
             */
            $processImage->save($originalDocumentPath, 100);
            /*
             * We need to re-hash file after being downscaled
             */
            $this->updateDocumentFileHash($originalDocument, $originalDocumentPath);

            $this->em->flush();

            return $originalDocument;
        } else {
            /*
             * If raw document size is inside new maxSize cap
             * we delete it and use it as new active document file.
             */
            $rawDocument = $originalDocument->getRawDocument();
            if (null !== $rawDocument) {
                $originalDocumentPath = $this->packages->getDocumentFilePath($originalDocument);
                $rawDocumentPath = $this->packages->getDocumentFilePath($rawDocument);

                /*
                 * Remove existing downscaled document.
                 */
                $fs->remove($originalDocumentPath);
                $fs->copy($rawDocumentPath, $originalDocumentPath, true);

                /*
                 * Remove Raw document
                 */
                $originalDocument->setRawDocument(null);
                /*
                 * We need to re-hash file after being downscaled
                 */
                $this->updateDocumentFileHash($originalDocument, $originalDocumentPath);
                /*
                 * Make sure to disconnect raw document before removing it
                 * not to trigger Cascade deleting.
                 */
                $this->em->flush();
                $this->em->remove($rawDocument);
                $this->em->flush();
            }

            return $originalDocument;
        }
    }
}
