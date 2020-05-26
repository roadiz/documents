<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Document\DocumentFinderInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Twig\Environment;

class AudioRenderer extends AbstractRenderer
{
    /**
     * @var Packages
     */
    private $packages;
    /**
     * @var DocumentFinderInterface
     */
    private $documentFinder;

    /**
     * AudioRenderer constructor.
     *
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
        parent::__construct($templating, $documentUrlGenerator, $templateBasePath);
        $this->packages = $packages;
        $this->documentFinder = $documentFinder;
    }

    public function supports(DocumentInterface $document, array $options): bool
    {
        return $document->isAudio();
    }

    /**
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return string
     */
    public function render(DocumentInterface $document, array $options): string
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);

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
        $basename = pathinfo($document->getFilename());
        $basename = $basename['filename'];

        $sources = [];
        $sourcesDocsName = [
            $basename . '.mp3',
            $basename . '.ogg',
            $basename . '.wav',
        ];

        $sourcesDocs = $this->documentFinder->findAllByFilenames($sourcesDocsName);

        /** @var DocumentInterface $source */
        foreach ($sourcesDocs as $source) {
            $sources[$source->getMimeType()] = [
                'mime' => $source->getMimeType(),
                'url' => $this->packages->getUrl($source->getRelativePath() ?? '', Packages::DOCUMENTS),
            ];
        }

        krsort($sources);

        return $sources;
    }
}
