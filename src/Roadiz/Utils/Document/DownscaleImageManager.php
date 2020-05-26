<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Document;

use Doctrine\ORM\EntityManagerInterface;
use Intervention\Image\Constraint;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;

class DownscaleImageManager
{
    protected $maxPixelSize = 0;
    protected $rawImageSuffix = ".raw";

    /**
     * @var ImageManager
     */
    protected $manager;
    /**
     * @var LoggerInterface|null
     */
    protected $logger;
    /**
     * @var EntityManagerInterface
     */
    protected $em;
    /**
     * @var Packages
     */
    private $packages;

    /**
     * DownscaleImageManager constructor.
     *
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
        LoggerInterface $logger = null,
        $imageDriver = 'gd',
        $maxPixelSize = 0,
        $rawImageSuffix = ".raw"
    ) {
        $this->maxPixelSize = (int) $maxPixelSize;
        $this->rawImageSuffix = $rawImageSuffix;
        $this->em = $em;
        $this->logger = $logger;
        $this->manager = new ImageManager(['driver' => $imageDriver]);
        $this->packages = $packages;
    }

    /**
     * Downscale document if needed, overriding raw document.
     *
     * @param  DocumentInterface|null $document
     */
    public function processAndOverrideDocument(DocumentInterface $document = null)
    {
        if (null !== $document && $this->maxPixelSize > 0) {
            $rawDocumentFilePath = $this->packages->getDocumentFilePath($document);
            $processImage = $this->getDownscaledImage($this->manager->make($rawDocumentFilePath));
            if (false !== $processImage) {
                if (false !== $this->createDocumentFromImage($document, $processImage) &&
                    null !== $this->logger) {
                    $this->logger->info('Document has been downscaled.', [
                        'path' => $rawDocumentFilePath
                    ]);
                }
            }
        }
    }

    /**
     * Downscale document if needed, keeping existing raw document.
     *
     * @param  DocumentInterface|null $document
     */
    public function processDocumentFromExistingRaw(DocumentInterface $document = null)
    {
        if (null !== $document && $this->maxPixelSize > 0) {
            if (null !== $document->getRawDocument()) {
                $rawDocumentFile = $this->packages->getDocumentFilePath($document->getRawDocument());
            } else {
                $rawDocumentFile = $this->packages->getDocumentFilePath($document);
            }

            if (false !== $processImage = $this->getDownscaledImage($this->manager->make($rawDocumentFile))) {
                if (false !== $this->createDocumentFromImage($document, $processImage, true) &&
                    null !== $this->logger) {
                    $this->logger->info('Document has been downscaled.', ['path' => $rawDocumentFile]);
                }
            }
        }
    }

    /**
     * Get downscaled image if size is higher than limit,
     * returns original image if lower or if image is a GIF.
     *
     * @param Image $processImage
     * @return Image|null
     */
    protected function getDownscaledImage(Image $processImage): ?Image
    {
        if ($processImage->mime() != 'image/gif' &&
            ($processImage->width() > $this->maxPixelSize || $processImage->height() > $this->maxPixelSize)) {
            // prevent possible upsizing
            $processImage->resize($this->maxPixelSize, $this->maxPixelSize, function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            return $processImage;
        } else {
            return null;
        }
    }

    /**
     * @param  DocumentInterface $originalDocument
     * @param  Image|null $processImage
     * @param  boolean $keepExistingRaw
     * @return DocumentInterface|bool Return new Document or FALSE
     */
    protected function createDocumentFromImage(
        DocumentInterface $originalDocument,
        Image $processImage = null,
        $keepExistingRaw = false
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
                    '#\.(jpe?g|gif|tiff?|png|psd)$#',
                    $this->rawImageSuffix . '.$1',
                    $originalDocument->getFilename()
                );
                if (null === $rawDocumentName) {
                    throw new \InvalidArgumentException('Raw document filename cannot be null');
                }
                $rawDocument->setFilename($rawDocumentName);

                $originalDocumentPath = $this->packages->getDocumentFilePath($originalDocument);
                $rawDocumentPath = $this->packages->getDocumentFilePath($rawDocument);

                if ($fs->exists($originalDocumentPath) &&
                    !$fs->exists($rawDocumentPath)) {
                    /*
                     * Original document path becomes raw document path. Rename it.
                     */
                    $fs->rename($originalDocumentPath, $rawDocumentPath);
                    /*
                     * Then save downscaled image as original document path.
                     */
                    $processImage->save($originalDocumentPath, 100);

                    $originalDocument->setRawDocument($rawDocument);
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
            $originalDocumentPath = $this->packages->getDocumentFilePath($originalDocument);

            /*
             * Remove existing downscaled document.
             */
            $fs->remove($originalDocumentPath);
            /*
             * Then save downscaled image as original document path.
             */
            $processImage->save($originalDocumentPath, 100);

            $this->em->flush();

            return $originalDocument;
        } else {
            /*
             * If raw document size is inside new maxSize cap
             * we delete it and use it as new active document file.
             */
            $rawDocument = $originalDocument->getRawDocument();

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
             * Make sure to disconnect raw document before removing it
             * not to trigger Cascade deleting.
             */
            $this->em->flush();
            $this->em->remove($rawDocument);
            $this->em->flush();

            return $originalDocument;
        }
    }
}
