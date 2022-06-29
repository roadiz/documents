<?php

declare(strict_types=1);

namespace RZ\Roadiz\Document\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Events\DocumentImageUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractExifDocumentSubscriber implements EventSubscriberInterface
{
    private Packages $packages;
    private LoggerInterface $logger;

    /**
     * @param Packages $packages
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Packages $packages,
        ?LoggerInterface $logger = null
    ) {
        $this->packages = $packages;
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentImageUploadedEvent::class => ['onImageUploaded', 101], // read EXIF before processing Raw documents
        ];
    }

    /**
     * @param  DocumentInterface $document
     * @return bool
     */
    protected function supports(DocumentInterface $document): bool
    {
        if (!$document->isLocal()) {
            return false;
        }
        if (!function_exists('exif_read_data')) {
            return false;
        }

        if ($document->getEmbedPlatform() !== "") {
            return false;
        }

        if ($document->getMimeType() == 'image/jpeg' || $document->getMimeType() == 'image/tiff') {
            return true;
        }

        return false;
    }

    /**
     * @param FilterDocumentEvent $event
     */
    public function onImageUploaded(FilterDocumentEvent $event): void
    {
        $document = $event->getDocument();
        if ($this->supports($document) && function_exists('exif_read_data')) {
            $filePath = $this->packages->getDocumentFilePath($document);
            $exif = @exif_read_data($filePath, 'FILE,COMPUTED,ANY_TAG,EXIF,COMMENT');

            if (false !== $exif) {
                $copyright = $this->getCopyright($exif);
                $description = $this->getDescription($exif);

                if (null !== $copyright || null !== $description) {
                    $this->logger->debug(
                        'EXIF information available for document.',
                        [
                            'document' => (string)$document
                        ]
                    );
                    $this->writeExifData($document, $copyright ?? '', $description ?? '');
                }
            }
        }
    }

    abstract protected function writeExifData(DocumentInterface $document, string $copyright, string $description): void;

    /**
     * @param  array $exif
     * @return string|null
     */
    protected function getCopyright(array $exif): ?string
    {
        foreach ($exif as $key => $section) {
            if (is_array($section)) {
                foreach ($section as $skey => $value) {
                    if (strtolower($skey) == 'copyright') {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array $exif
     * @return string|null
     */
    protected function getDescription(array $exif): ?string
    {
        foreach ($exif as $key => $section) {
            if (is_string($section) && strtolower($key) == 'imagedescription') {
                return $section;
            } elseif (is_array($section)) {
                if (strtolower($key) == 'comment') {
                    $comment = '';
                    foreach ($section as $value) {
                        $comment .= $value . PHP_EOL;
                    }
                    return $comment;
                } else {
                    foreach ($section as $skey => $value) {
                        if (strtolower($skey) == 'comment') {
                            return $value;
                        }
                    }
                }
            }
        }

        return null;
    }
}
