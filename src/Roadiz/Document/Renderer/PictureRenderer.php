<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;

class PictureRenderer extends AbstractImageRenderer
{
    public function supports(DocumentInterface $document, array $options): bool
    {
        return isset($options['picture']) &&
            $options['picture'] === true &&
            parent::supports($document, $options);
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);

        $assignation = array_merge(array_filter($options), [
            'mimetype' => $document->getMimeType(),
            'isWebp' => $document->isWebp(),
            'url' => $this->getSource($document, $options),
            'media' => null,
            'srcset' => null,
            'webp_srcset' => null,
            'mediaList' => null,
        ]);
        $assignation['alt'] = !empty($options['alt']) ? $options['alt'] : $document->getAlternativeText();
        $assignation['sizes'] = $this->parseSizes($options);

        if (count($options['media']) > 0) {
            $assignation['mediaList'] = $this->parseMedia($document, $options);
        } else {
            $assignation['srcset'] = $this->parseSrcSet($document, $options);
            if (!$document->isWebp()) {
                $assignation['webp_srcset'] = $this->parseSrcSet($document, $options, true);
            }
        }

        if (method_exists($document, 'getImageAverageColor') &&
            null !== $document->getImageAverageColor() &&
            $document->getImageAverageColor() !== '#ffffff' &&
            $document->getImageAverageColor() !== '#000000') {
            $assignation['averageColor'] = $document->getImageAverageColor();
            $assignation['fallback'] = $this->createTransparentDataURI($document->getImageAverageColor());
        }

        return $this->renderHtmlElement('picture.html.twig', $assignation);
    }

    private function parseMedia(DocumentInterface $document, array $options = []): array
    {
        $mediaList = [];
        foreach ($options['media'] as $media) {
            if (!isset($media['srcset'])) {
                throw new \InvalidArgumentException('Picture media list must have srcset option.');
            }
            if (!isset($media['rule'])) {
                throw new \InvalidArgumentException('Picture media list must have rule option.');
            }
            $mediaList[] = [
                'srcset' => $this->parseSrcSetInner($document, $media['srcset'], false, $options['absolute']),
                'webp_srcset' => !$document->isWebp() ? $this->parseSrcSetInner($document, $media['srcset'], true, $options['absolute']) : null,
                'rule' => $media['rule']
            ];
        }
        return $mediaList;
    }
}
