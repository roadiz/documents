<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\Document;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Asset\Packages;

final class SvgSizeResolver
{
    private DocumentInterface $document;
    private Packages $packages;
    private ?\DOMDocument $xmlDocument = null;
    private ?\DOMNode $svgNode = null;

    /**
     * @param DocumentInterface $document
     * @param Packages $packages
     */
    public function __construct(DocumentInterface $document, Packages $packages)
    {
        $this->document = $document;
        $this->packages = $packages;
    }

    /**
     * First, find width attr, then resolve width from viewBox.
     *
     * @return int
     */
    public function getWidth(): int
    {
        try {
            $width = $this->getSvgNodeAttributes()->getNamedItem('width');
            $viewBox = $this->getSvgNodeAttributes()->getNamedItem('viewBox');
        } catch (\RuntimeException $exception) {
            return 0;
        }

        if (null !== $width &&
            $width->textContent !== "" &&
            false === strpos($width->textContent, '%')) {
            return (int) $width->textContent;
        }
        if (null !== $viewBox && $viewBox->textContent !== "") {
            [$x, $y, $width, $height] = explode(' ', $viewBox->textContent);
            return (int) $width;
        }

        return 0;
    }

    /**
     * First, find height attr, then resolve height from viewBox.
     *
     * @return int
     */
    public function getHeight(): int
    {
        try {
            $height = $this->getSvgNodeAttributes()->getNamedItem('height');
            $viewBox = $this->getSvgNodeAttributes()->getNamedItem('viewBox');
        } catch (\RuntimeException $exception) {
            return 0;
        }

        if (null !== $height &&
            $height->textContent !== "" &&
            false === strpos($height->textContent, '%')) {
            return (int) $height->textContent;
        }
        if (null !== $viewBox && $viewBox->textContent !== "") {
            [$x, $y, $width, $height] = explode(' ', $viewBox->textContent);
            return (int) $height;
        }

        return 0;
    }

    private function getSvgNode(): \DOMElement
    {
        if (null === $this->svgNode) {
            $svg = $this->getDOMDocument()->getElementsByTagName('svg');
            if (!isset($svg[0])) {
                throw new \RuntimeException('SVG does not contain a valid <svg> tag');
            }
            $this->svgNode = $svg[0];
        }

        return $this->svgNode;
    }

    private function getSvgNodeAttributes(): \DOMNamedNodeMap
    {
        if (null === $this->getSvgNode()->attributes) {
            throw new \RuntimeException('SVG tag <svg> does not contain any attribute');
        }

        return $this->getSvgNode()->attributes;
    }

    private function getDOMDocument(): \DOMDocument
    {
        if (null === $this->xmlDocument) {
            $this->xmlDocument = new \DOMDocument();
            $documentPath = $this->packages->getDocumentFilePath($this->document);
            if (false === $this->xmlDocument->load($documentPath)) {
                throw new \RuntimeException(sprintf('SVG (%s) could not be loaded.', $documentPath));
            }
        }
        return $this->xmlDocument;
    }
}
