<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file DocumentUrlGenerator.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SymfonyUrlGeneratorInterface;

class DocumentUrlGenerator
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var DocumentInterface
     */
    private $document;

    /** @var array */
    private $options;
    /**
     * @var Packages
     */
    private $packages;
    /**
     * @var SymfonyUrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * DocumentUrlGenerator constructor.
     * @param RequestStack $requestStack
     * @param Packages $packages
     * @param SymfonyUrlGeneratorInterface $urlGenerator
     * @param DocumentInterface|null $document
     * @param array $options
     */
    public function __construct(
        RequestStack $requestStack,
        Packages $packages,
        SymfonyUrlGeneratorInterface $urlGenerator,
        DocumentInterface $document = null,
        array $options = []
    ) {
        $this->requestStack = $requestStack;
        $this->document = $document;
        $this->packages = $packages;
        $this->urlGenerator = $urlGenerator;

        $this->setOptions($options);
    }

    public function setOptions(array $options = [])
    {
        $resolver = new ViewOptionsResolver();
        $this->options = $resolver->resolve($options);
    }

    /**
     * @return DocumentInterface
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param DocumentInterface $document
     * @return DocumentUrlGenerator
     */
    public function setDocument(DocumentInterface $document)
    {
        $this->document = $document;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUrl($absolute = false)
    {
        if ($this->options['noProcess'] === true || !$this->document->isImage()) {
            $documentPackageName = $absolute ? Packages::ABSOLUTE_DOCUMENTS : Packages::DOCUMENTS;
            return $this->packages->getUrl(
                ltrim($this->document->getRelativePath(), '/'),
                $documentPackageName
            );
        }

        $referenceType = $absolute ? SymfonyUrlGeneratorInterface::ABSOLUTE_URL : SymfonyUrlGeneratorInterface::ABSOLUTE_PATH;

        return $this->getProcessedDocumentUrlByArray($referenceType);
    }

    /**
     * @return string
     */
    protected function getRouteName()
    {
        return 'interventionRequestProcess';
    }

    /**
     * @param int $referenceType The type of reference to be generated (one of the UrlGeneratorInterface constants)
     * @return string
     */
    protected function getProcessedDocumentUrlByArray($referenceType = SymfonyUrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $compiler = new OptionsCompiler();

        $routeParams = [
            'queryString' => $compiler->compile($this->options),
            'filename' => $this->document->getRelativePath(),
        ];

        return $this->urlGenerator->generate(
            $this->getRouteName(),
            $routeParams,
            $referenceType
        );
    }
}
