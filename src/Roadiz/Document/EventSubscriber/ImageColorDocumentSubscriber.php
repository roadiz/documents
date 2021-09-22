<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\EventSubscriber;

use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Events\DocumentImageUploadedEvent;
use RZ\Roadiz\Core\Events\FilterDocumentEvent;
use RZ\Roadiz\Core\Models\DisplayableInterface;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\AverageColorResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ImageColorDocumentSubscriber implements EventSubscriberInterface
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
        return $document->isLocal() && $document->isProcessable();
    }

    /**
     * @param FilterDocumentEvent $event
     */
    public function onImageUploaded(FilterDocumentEvent $event): void
    {
        $document = $event->getDocument();
        if ($this->supports($document) && $document instanceof DisplayableInterface) {
            $documentPath = $this->packages->getDocumentFilePath($document);
            try {
                $manager = new ImageManager();
                $mediumColor = (new AverageColorResolver())->getAverageColor($manager->make($documentPath));
                $document->setImageAverageColor($mediumColor);
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
