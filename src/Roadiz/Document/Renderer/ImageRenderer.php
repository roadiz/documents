<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;

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

        $assignation = array_merge(array_filter($options), [
            'mimetype' => $document->getMimeType(),
            'url' => $this->getSource($document, $options),
            'media' => null
        ]);
        $assignation['alt'] = !empty($options['alt']) ? $options['alt'] : $document->getAlternativeText();
        $assignation['sizes'] = $this->parseSizes($options);
        $assignation['srcset'] = $this->parseSrcSet($document, $options);

        $this->additionalAssignation($document, $options, $assignation);

        return $this->renderHtmlElement('image.html.twig', $assignation);
    }
}
