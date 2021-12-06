<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\AdvancedDocumentInterface;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\HasThumbnailInterface;

class ImageRenderer extends AbstractImageRenderer
{
    public function supports(DocumentInterface $document, array $options): bool
    {
        return (!isset($options['picture']) || $options['picture'] === false) &&
            parent::supports($document, $options);
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $options = $this->viewOptionsResolver->resolve($options);

        /*
         * Override image by its first thumbnail if existing
         */
        if (!$options['no_thumbnail'] && $document instanceof HasThumbnailInterface && $document->hasThumbnails()) {
            $document = $document->getThumbnails()->first();
        }

        $assignation = array_merge(
            array_filter($options),
            [
                'mimetype' => $document->getMimeType(),
                'url' => $this->getSource($document, $options),
                'media' => null
            ]
        );
        $assignation['alt'] = !empty($options['alt']) ? $options['alt'] : $document->getAlternativeText();
        $assignation['sizes'] = $this->parseSizes($options);
        $assignation['srcset'] = $this->parseSrcSet($document, $options);

        if (null === $assignation['sizes']
            && $document instanceof AdvancedDocumentInterface
            && $document->getImageWidth() > 0
            && $document->getImageHeight() > 0
            && !$this->willResample($assignation)
        ) {
            $assignation['width'] = $document->getImageWidth();
            $assignation['height'] = $document->getImageHeight();
        }

        $this->additionalAssignation($document, $options, $assignation);

        return $this->renderHtmlElement('image.html.twig', $assignation);
    }
}
