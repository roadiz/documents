<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use Doctrine\Common\Cache\CacheProvider;
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
    protected ?CacheProvider $optionsCacheProvider;
    protected ViewOptionsResolver $viewOptionsResolver;
    protected OptionsCompiler $optionCompiler;

    /**
     * @param Packages               $packages
     * @param DocumentInterface|null $document
     * @param array                  $options
     * @param CacheProvider|null     $optionsCacheProvider
     */
    public function __construct(
        Packages $packages,
        DocumentInterface $document = null,
        array $options = [],
        ?CacheProvider $optionsCacheProvider = null
    ) {
        $this->document = $document;
        $this->packages = $packages;
        $this->optionsCacheProvider = $optionsCacheProvider;
        $this->viewOptionsResolver = new ViewOptionsResolver();
        $this->optionCompiler = new OptionsCompiler();

        $this->setOptions($options);
    }

    /**
     * @param array $options
     *
     * @return AbstractDocumentUrlGenerator
     */
    public function setOptions(array $options = [])
    {
        if (null !== $this->optionsCacheProvider) {
            /*
             * Use optionsCacheProvider to resolve valid options once, especially if
             * you are rendering a lot of documents with the same options.
             */
            $optionsHash = md5(json_encode($options) ?: '');
            if (!$this->optionsCacheProvider->contains($optionsHash)) {
                $resolvedOptions = $this->viewOptionsResolver->resolve($options);
                $this->optionsCacheProvider->save($optionsHash, $resolvedOptions);
            }
            $this->options = $this->optionsCacheProvider->fetch($optionsHash) ?: [];
        } else {
            $this->options = $this->viewOptionsResolver->resolve($options);
        }
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
     * @param bool $absolute
     * @return string
     */
    abstract protected function getProcessedDocumentUrlByArray(bool $absolute = false): string;
}
