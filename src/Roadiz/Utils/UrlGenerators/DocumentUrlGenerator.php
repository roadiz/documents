<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use Doctrine\Common\Cache\CacheProvider;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as SymfonyUrlGeneratorInterface;

class DocumentUrlGenerator implements DocumentUrlGeneratorInterface
{
    /**
     * @var RequestStack
     * @deprecated Useless and creates dependency
     */
    private $requestStack;
    /**
     * @var DocumentInterface|null
     */
    private $document;
    /**
     * @var array
     */
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
     * @var ViewOptionsResolver
     */
    private $viewOptionsResolver;
    /**
     * @var OptionsCompiler
     */
    private $optionCompiler;

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
        array $options = [],
        ?CacheProvider $optionCacheProvider = null
    ) {
        $this->requestStack = $requestStack;
        $this->document = $document;
        $this->packages = $packages;
        $this->urlGenerator = $urlGenerator;
        $this->viewOptionsResolver = new ViewOptionsResolver();
        $this->optionCompiler = new OptionsCompiler();
        $this->cache = $optionCacheProvider;

        $this->setOptions($options);
    }

    /**
     * @param array $options
     *
     * @return DocumentUrlGenerator
     */
    public function setOptions(array $options = [])
    {
        if (null !== $this->cache) {
            /*
             * Use cache to resolve valid options once, especially if
             * you are rendering a lot of documents with the same options.
             */
            $optionsHash = md5(json_encode($options));
            if (!$this->cache->contains($optionsHash)) {
                $resolvedOptions = $this->viewOptionsResolver->resolve($options);
                $this->cache->save($optionsHash, $resolvedOptions);
            }
            $this->options = $this->cache->fetch($optionsHash);
        } else {
            $this->options = $this->viewOptionsResolver->resolve($options);
        }
        return $this;
    }

    /**
     * @return DocumentInterface|null
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
     * @param bool $absolute
     *
     * @return string
     */
    public function getUrl(bool $absolute = false): string
    {
        if (null === $this->document) {
            throw new \InvalidArgumentException('Cannot get URL from a NULL document');
        }

        if ($this->options['noProcess'] === true || !$this->document->isProcessable()) {
            $documentPackageName = $absolute ? Packages::ABSOLUTE_DOCUMENTS : Packages::DOCUMENTS;
            return $this->packages->getUrl(
                ltrim($this->document->getRelativePath() ?? '', '/'),
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
        if (null === $this->document) {
            throw new \InvalidArgumentException('Cannot get URL from a NULL document');
        }

        $routeParams = [
            'queryString' => $this->optionCompiler->compile($this->options),
            'filename' => $this->document->getRelativePath(),
        ];

        return $this->urlGenerator->generate(
            $this->getRouteName(),
            $routeParams,
            $referenceType
        );
    }
}
