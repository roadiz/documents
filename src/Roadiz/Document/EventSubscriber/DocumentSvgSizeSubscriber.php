<?php

declare(strict_types=1);

namespace RZ\Roadiz\Document\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Events\DocumentSvgUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\SizeableInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\SvgSizeResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @deprecated Document processing subscribers are deprecated in favor of async messaging
 */
final class DocumentSvgSizeSubscriber implements EventSubscriberInterface
{
    private Packages $packages;
    private LoggerInterface $logger;

    /**
     * @param Packages             $packages
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
            DocumentSvgUploadedEvent::class => ['onImageUploaded', 0],
        ];
    }

    /**
     * @param  DocumentInterface $document
     * @return bool
     */
    protected function supports(DocumentInterface $document): bool
    {
        if ($document->isLocal() && $document->isSvg()) {
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
        if ($this->supports($document) && $document instanceof SizeableInterface) {
            try {
                $svgSizeResolver = new SvgSizeResolver($document, $this->packages);
                $document->setImageWidth($svgSizeResolver->getWidth());
                $document->setImageHeight($svgSizeResolver->getHeight());
            } catch (\RuntimeException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}
