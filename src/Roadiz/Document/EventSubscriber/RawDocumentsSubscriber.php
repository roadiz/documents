<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\EventSubscriber;

use RZ\Roadiz\Core\Events\DocumentImageUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Utils\Document\DownscaleImageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create a raw image and downscale it to a new image file for a better web usage.
 */
final class RawDocumentsSubscriber implements EventSubscriberInterface
{
    private DownscaleImageManager $downscaleImageManager;

    /**
     * @param DownscaleImageManager $downscaleImageManager
     */
    public function __construct(
        DownscaleImageManager $downscaleImageManager
    ) {
        $this->downscaleImageManager = $downscaleImageManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            // Keeps Raw document process before any other document subscribers to perform operations
            // on a lower image
            DocumentImageUploadedEvent::class => ['onImageUploaded', 100],
        ];
    }

    public function onImageUploaded(FilterDocumentEvent $event)
    {
        if (null !== $event->getDocument() && $event->getDocument()->isProcessable()) {
            $this->downscaleImageManager->processAndOverrideDocument($event->getDocument());
        }
    }
}
