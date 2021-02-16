<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\MediaFinders\EmbedFinderFactory;

class EmbedRenderer implements RendererInterface
{
    protected EmbedFinderFactory $embedFinderFactory;

    /**
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
        try {
            $finder = $this->embedFinderFactory->createForPlatform(
                $document->getEmbedPlatform(),
                $document->getEmbedId()
            );
            if (null !== $finder) {
                return $finder->getIFrame($options);
            }
            return '';
        } catch (InvalidEmbedId $exception) {
            return '<p>'.$exception->getMessage().'</p>';
        }
    }
}
