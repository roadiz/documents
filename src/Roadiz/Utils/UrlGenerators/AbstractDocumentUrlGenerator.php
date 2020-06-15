<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;

/**
 * Class AbstractDocumentUrlGenerator
 *
 * @package RZ\Roadiz\Utils\UrlGenerators
 */
abstract class AbstractDocumentUrlGenerator implements DocumentUrlGeneratorInterface
{
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
     * AbstractDocumentUrlGenerator constructor.
     *
     * @param Packages               $packages
     * @param DocumentInterface|null $document
     * @param array                  $options
     */
    public function __construct(
        Packages $packages,
        DocumentInterface $document = null,
        array $options = []
    ) {
        $this->document = $document;
        $this->packages = $packages;

        $this->setOptions($options);
    }

    /**
     * @param array $options
     *
     * @return AbstractDocumentUrlGenerator
     */
    public function setOptions(array $options = [])
    {
        $resolver = new ViewOptionsResolver();
        $this->options = $resolver->resolve($options);
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
