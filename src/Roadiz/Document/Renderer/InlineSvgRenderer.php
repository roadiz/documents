<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Viewers\SvgDocumentViewer;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class InlineSvgRenderer implements RendererInterface
{
    protected Packages $packages;
    protected ViewOptionsResolver $viewOptionsResolver;

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
        return $document->isSvg() && (isset($options['inline']) && $options['inline'] === true);
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $options = $this->viewOptionsResolver->resolve($options);
        $assignation = array_filter($options);

        try {
            $viewer = new SvgDocumentViewer(
                $this->packages->getDocumentFilePath($document),
                $assignation
            );
            return trim($this->htmlTidy($viewer->getContent()));
        } catch (FileNotFoundException $e) {
            return '<p>SVG file was not found</p>';
        }
    }

    protected function htmlTidy(string $body): string
    {
        return preg_replace('#\>[\n\r\s]+\<#', '><', $body) ?? '';
    }
}
