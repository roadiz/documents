<?php

declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Document\DocumentFinderInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Twig\Environment;

class AudioRenderer extends AbstractRenderer
{
    protected DocumentFinderInterface $documentFinder;

    /**
     * @param Packages                      $packages
     * @param DocumentFinderInterface       $documentFinder
     * @param Environment                   $templating
     * @param DocumentUrlGeneratorInterface $documentUrlGenerator
     * @param string                        $templateBasePath
     */
    public function __construct(
        Packages $packages,
        DocumentFinderInterface $documentFinder,
        Environment $templating,
        DocumentUrlGeneratorInterface $documentUrlGenerator,
        string $templateBasePath = 'documents'
    ) {
        parent::__construct($packages, $templating, $documentUrlGenerator, $templateBasePath);
        $this->documentFinder = $documentFinder;
    }

    public function supports(DocumentInterface $document, array $options): bool
    {
        return $document->isAudio();
    }

    /**
     * @param DocumentInterface $document
     * @param array $options
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(DocumentInterface $document, array $options): string
    {
        $options = $this->viewOptionsResolver->resolve($options);
        $assignation = array_filter($options);
        $assignation['sources'] = $this->getSourcesFiles($document);

        return $this->renderHtmlElement('audio.html.twig', $assignation);
    }

    /**
     * Get sources files formats for audio and video documents.
     *
     * This method will search for document which filename is the same
     * except the extension. If you choose an MP4 file, it will look for a OGV and WEBM file.
     *
     * @param DocumentInterface $document
     *
     * @return array
     */
    protected function getSourcesFiles(DocumentInterface $document): array
    {
        if (!$document->isLocal()) {
            return [];
        }

        return $this->getSourcesFilesArray(
            $document,
            $this->documentFinder->findAudiosWithFilename($document->getFilename())
        );
    }
}
