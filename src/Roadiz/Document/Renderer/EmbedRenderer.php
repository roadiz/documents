<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\MediaFinders\EmbedFinderFactory;

class EmbedRenderer implements RendererInterface
{
    /**
     * @var EmbedFinderFactory
     */
    protected $embedFinderFactory;

    /**
     * EmbedRenderer constructor.
     *
     * @param EmbedFinderFactory $embedFinderFactory
     */
    public function __construct(EmbedFinderFactory $embedFinderFactory)
    {
        $this->embedFinderFactory = $embedFinderFactory;
    }

    public function supports(DocumentInterface $document, array $options): bool
    {
        if ($document->isEmbed() &&
            $this->embedFinderFactory->supports($document->getEmbedPlatform()) &&
            isset($options['embed']) &&
            $options['embed'] === true
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $finder = $this->embedFinderFactory->createForPlatform($document->getEmbedPlatform(), $document->getEmbedId());
        return $finder->getIFrame($options);
    }
}
