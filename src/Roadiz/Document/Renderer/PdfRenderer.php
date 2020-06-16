<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;

class PdfRenderer extends AbstractRenderer
{
    public function supports(DocumentInterface $document, array $options): bool
    {
        return $document->isPdf() &&
            key_exists('embed', $options) &&
            $options['embed'] === true;
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $options = $this->viewOptionsResolver->resolve($options);

        $assignation = array_merge(array_filter($options), [
            'url' => $this->getSource($document, $options),
        ]);

        return $this->renderHtmlElement('pdf.html.twig', $assignation);
    }
}
