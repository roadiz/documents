<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Twig\Environment;

abstract class AbstractRenderer implements RendererInterface
{
    /**
     * @var Environment
     */
    protected $templating;
    /**
     * @var string
     */
    protected $templateBasePath;
    /**
     * @var DocumentUrlGeneratorInterface
     */
    protected $documentUrlGenerator;

    /**
     * AbstractRenderer constructor.
     *
     * @param Environment                   $templating
     * @param DocumentUrlGeneratorInterface $documentUrlGenerator
     * @param string                        $templateBasePath
     */
    public function __construct(
        Environment $templating,
        DocumentUrlGeneratorInterface $documentUrlGenerator,
        string $templateBasePath = 'documents'
    ) {
        $this->templating = $templating;
        $this->templateBasePath = $templateBasePath;
        $this->documentUrlGenerator = $documentUrlGenerator;
    }

    /**
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return string
     */
    protected function getSource(DocumentInterface $document, array $options): string
    {
        $this->documentUrlGenerator->setOptions($options);
        $this->documentUrlGenerator->setDocument($document);
        return $this->documentUrlGenerator->getUrl($options['absolute']);
    }

    /**
     * @param string $template
     * @param array  $assignation
     *
     * @return string
     */
    protected function renderHtmlElement(string $template, array $assignation): string
    {
        return $this->templating->render($this->templateBasePath . '/' . $template, $assignation);
    }
}
