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
        return $document->isSvg() && (isset($options['inline']) && $options['inline'] === true);
    }

    public function render(DocumentInterface $document, array $options): string
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);
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
        return preg_replace('#\>[\n\r\s]+\<#', '><', $body);
    }
}