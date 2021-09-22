<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\EventSubscriber;

use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Events\DocumentImageUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Models\AdvancedDocumentInterface;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DocumentSizeSubscriber implements EventSubscriberInterface
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
            DocumentImageUploadedEvent::class => ['onImageUploaded', 0],
        ];
    }

    /**
     * @param  DocumentInterface $document
     * @return bool
     */
    protected function supports(DocumentInterface $document): bool
    {
        return $document->isLocal() && $document->isImage();
    }

    /**
     * @param FilterDocumentEvent $event
     */
    public function onImageUploaded(FilterDocumentEvent $event): void
    {
        $document = $event->getDocument();
        if ($this->supports($document) && $document instanceof AdvancedDocumentInterface) {
            $documentPath = $this->packages->getDocumentFilePath($document);
            try {
                $manager = new ImageManager();
                $imageProcess = $manager->make($documentPath);
                $document->setImageWidth($imageProcess->width());
                $document->setImageHeight($imageProcess->height());
            } catch (NotReadableException $exception) {
                $this->logger->warning(
                    'Document file is not a readable image.',
                    [
                        'path' => $documentPath,
                    ]
                );
            }
        }
    }
}
