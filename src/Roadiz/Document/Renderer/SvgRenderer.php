<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Viewers\SvgDocumentViewer;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;

class SvgRenderer implements RendererInterface
{
    /**
     * @var Packages
     */
    protected $packages;

    /**
     * @var ViewOptionsResolver
     */
    protected $viewOptionsResolver;

    /**
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
        $this->viewOptionsResolver = new ViewOptionsResolver();
    }

    public function supports(DocumentInterface $document, array $options): bool
    {
        return $document->isSvg() && (!isset($options['inline']) || $options['inline'] === false);
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $options = $this->viewOptionsResolver->resolve($options);
        $assignation = array_filter($options);
        $attributes = $this->getAttributes($assignation);
        $attributes['src'] = $this->packages->getUrl(
            $document->getRelativePath() ?? '',
            Packages::DOCUMENTS
        );

        $attrs = [];
        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $value = htmlspecialchars($value);
            }
            $attrs[] = $key . '="' . $value . '"';
        }

        return '<img ' . implode(' ', $attrs) . ' />';
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function getAttributes(array $options): array
    {
        $attributes = [];
        $allowedAttributes = array_merge(
            SvgDocumentViewer::$allowedAttributes,
            [
                'loading',
                'alt'
            ]
        );
        foreach ($options as $key => $value) {
            if (in_array($key, $allowedAttributes)) {
                if ($key === 'identifier') {
                    $attributes['id'] = $value;
                } else {
                    $attributes[$key] = $value;
                }
            }
        }
        return $attributes;
    }
}
