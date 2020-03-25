<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;

class DummyDocumentUrlGenerator implements DocumentUrlGeneratorInterface
{
    /** @var DocumentInterface */
    private $document;
    /** @var array  */
    private $options = [];
    /** @var Packages  */
    private $packages;

    /**
     * DummyDocumentUrlGenerator constructor.
     *
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    public function getUrl(bool $absolute = false): string
    {
        if (!key_exists('noProcess', $this->options)) {
            throw new \InvalidArgumentException('noProcess option is not set');
        }

        if ($this->options['noProcess'] === true || !$this->document->isProcessable()) {
            $documentPackageName = $absolute ? Packages::ABSOLUTE_DOCUMENTS : Packages::DOCUMENTS;
            return $this->packages->getUrl(
                ltrim($this->document->getRelativePath(), DIRECTORY_SEPARATOR),
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

    public function setDocument(DocumentInterface $document)
    {
        $this->document = $document;
        return $this;
    }

    public function setOptions(array $options = [])
    {
        $this->options = $options;
        return $this;
    }
}
