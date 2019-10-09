<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;

class ImageRenderer extends AbstractImageRenderer
{
    public function supports(DocumentInterface $document, array $options): bool
    {
        return $document->isImage() && (!isset($options['picture']) || $options['picture'] === false);
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);

        $assignation = array_merge(array_filter($options), [
            'mimetype' => $document->getMimeType(),
            'url' => $this->getSource($document, $options),
        ]);
        $assignation['alt'] = !empty($options['alt']) ? $options['alt'] : $document->getAlternativeText();
        $assignation['sizes'] = $this->parseSizes($options);
        $assignation['srcset'] = $this->parseSrcSet($document, $options);

        return $this->renderHtmlElement('image.html.twig', $assignation);
    }
}
