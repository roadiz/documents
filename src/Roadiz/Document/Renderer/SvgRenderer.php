<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Viewers\SvgDocumentViewer;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class SvgRenderer implements RendererInterface
{
    /**
     * @var Packages
     */
    private $packages;

    /**
     * SvgRenderer constructor.
     *
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    public function supports(DocumentInterface $document, array $options): bool
    {
        return $document->isSvg();
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);
        $assignation = array_filter($options);

        if ($options['inline']) {
            try {
                $viewer = new SvgDocumentViewer(
                    $this->packages->getDocumentFilePath($document),
                    $assignation
                );
                return $viewer->getContent();
            } catch (FileNotFoundException $e) {
                return '<p>SVG file was not found</p>';
            }
        }

        $attributes = $this->getAttributes($options);
        $attributes['data'] = $this->packages->getUrl($document->getRelativePath(), Packages::DOCUMENTS);

        $attrs = [];
        foreach ($attributes as $key => $value) {
            $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
        }

        return '<object ' . implode(' ', $attrs) . '></object>';
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function getAttributes(array $options): array
    {
        $attributes = [
            'type' => 'image/svg+xml'
        ];
        foreach ($options as $key => $value) {
            if (in_array($key, SvgDocumentViewer::$allowedAttributes)) {
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
