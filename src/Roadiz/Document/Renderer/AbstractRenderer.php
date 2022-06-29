<?php

declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Exceptions\DocumentWithoutFileException;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Document\UrlOptionsResolver;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Twig\Environment;

abstract class AbstractRenderer implements RendererInterface
{
    protected Environment $templating;
    protected DocumentUrlGeneratorInterface $documentUrlGenerator;
    protected string $templateBasePath;
    protected UrlOptionsResolver $urlOptionsResolver;
    protected ViewOptionsResolver $viewOptionsResolver;

    /**
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
        $this->documentUrlGenerator = $documentUrlGenerator;
        $this->templateBasePath = $templateBasePath;
        $this->urlOptionsResolver = new UrlOptionsResolver();
        $this->viewOptionsResolver = new ViewOptionsResolver();
    }

    /**
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return string
     */
    protected function getSource(DocumentInterface $document, array $options): string
    {
        if (empty($document->getRelativePath())) {
            throw new DocumentWithoutFileException($document);
        }
        $this->documentUrlGenerator->setOptions($options);
        $this->documentUrlGenerator->setDocument($document);
        return $this->documentUrlGenerator->getUrl($options['absolute']);
    }

    /**
     * @param string $template
     * @param array $assignation
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function renderHtmlElement(string $template, array $assignation): string
    {
        return $this->templating->render($this->templateBasePath . '/' . $template, $assignation);
    }
}
