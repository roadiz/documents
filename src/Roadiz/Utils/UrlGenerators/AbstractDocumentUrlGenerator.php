<?php

declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use Psr\Cache\CacheItemPoolInterface;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;

/**
 * @package RZ\Roadiz\Utils\UrlGenerators
 */
abstract class AbstractDocumentUrlGenerator implements DocumentUrlGeneratorInterface
{
    protected Packages $packages;
    protected ?DocumentInterface $document;
    protected array $options;
    protected CacheItemPoolInterface $optionsCacheAdapter;
    protected ViewOptionsResolver $viewOptionsResolver;
    protected OptionsCompiler $optionCompiler;

    /**
     * @param Packages $packages
     * @param CacheItemPoolInterface $optionsCacheAdapter
     * @param DocumentInterface|null $document
     * @param array $options
     */
    public function __construct(
        Packages $packages,
        CacheItemPoolInterface $optionsCacheAdapter,
        DocumentInterface $document = null,
        array $options = []
    ) {
        $this->document = $document;
        $this->packages = $packages;
        $this->viewOptionsResolver = new ViewOptionsResolver();
        $this->optionCompiler = new OptionsCompiler();
        $this->optionsCacheAdapter = $optionsCacheAdapter;

        $this->setOptions($options);
    }

    /**
     * @param array $options
     * @return AbstractDocumentUrlGenerator
     */
    public function setOptions(array $options = [])
    {
        $optionsCacheItem = $this->optionsCacheAdapter->getItem(md5(json_encode($options) ?: ''));
        if (!$optionsCacheItem->isHit()) {
            $resolvedOptions = $this->viewOptionsResolver->resolve($options);
            $optionsCacheItem->set($resolvedOptions);
            $this->optionsCacheAdapter->save($optionsCacheItem);
        }
        $this->options = $optionsCacheItem->get() ?: [];

        return $this;
    }

    /**
     * @return DocumentInterface|null
     */
    public function getDocument(): ?DocumentInterface
    {
        return $this->document;
    }

    /**
     * @param DocumentInterface $document
     *
     * @return AbstractDocumentUrlGenerator
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

        return $this->getProcessedDocumentUrlByArray($absolute);
    }

    /**
     * @param  bool $absolute
     * @return string
     */
    abstract protected function getProcessedDocumentUrlByArray(bool $absolute = false): string;
}
