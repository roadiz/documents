<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\UrlGenerators;

use RZ\Roadiz\Documents\Models\DocumentInterface;
use RZ\Roadiz\Documents\Packages;

class DummyDocumentUrlGenerator implements DocumentUrlGeneratorInterface
{
    private ?DocumentInterface $document = null;
    private array $options = [];
    private Packages $packages;

    /**
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    public function getUrl(bool $absolute = false): string
    {
        if (null === $this->document) {
            throw new \BadMethodCallException('Document is null');
        }
        if (!key_exists('noProcess', $this->options)) {
            throw new \BadMethodCallException('noProcess option is not set');
        }

        if ($this->options['noProcess'] === true || !$this->document->isProcessable()) {
            $documentPackageName = $absolute ? Packages::ABSOLUTE_DOCUMENTS : Packages::DOCUMENTS;
            return $this->packages->getUrl(
                ltrim($this->document->getRelativePath() ?? '', '/'),
                $documentPackageName
            );
        }

        $compiler = new OptionsCompiler();
        $compiledOptions = $compiler->compile($this->options);

        if ($absolute) {
            return 'http://dummy.test/assets/' . $compiledOptions . '/' . $this->document->getRelativePath();
        }
        return '/assets/' . $compiledOptions . '/' . $this->document->getRelativePath();
    }

    public function setDocument(DocumentInterface $document): static
    {
        $this->document = $document;
        return $this;
    }

    public function setOptions(array $options = []): static
    {
        $this->options = $options;
        return $this;
    }
}
