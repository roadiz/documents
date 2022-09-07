<?php

declare(strict_types=1);

namespace RZ\Roadiz\Document\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RZ\Roadiz\Core\Events\DocumentFileUploadedEvent;
use RZ\Roadiz\Core\Models\AdvancedDocumentInterface;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @deprecated Document processing subscribers are deprecated in favor of async messaging
 */
final class DocumentFilesizeSubscriber implements EventSubscriberInterface
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
            DocumentFileUploadedEvent::class => ['onFileUploaded', 0],
        ];
    }

    /**
     * @param  DocumentInterface $document
     * @return bool
     */
    protected function supports(DocumentInterface $document): bool
    {
        return $document->isLocal() && null !== $document->getRelativePath();
    }

    /**
     * @param DocumentFileUploadedEvent $event
     */
    public function onFileUploaded(DocumentFileUploadedEvent $event): void
    {
        $document = $event->getDocument();
        if ($this->supports($document) && $document instanceof AdvancedDocumentInterface) {
            $documentPath = $this->packages->getDocumentFilePath($document);
            try {
                $file = new File($documentPath);
                $document->setFilesize($file->getSize());
            } catch (FileNotFoundException $exception) {
                $this->logger->warning(
                    'Document file not found.',
                    [
                        'path' => $documentPath,
                    ]
                );
            }
        }
    }
}
