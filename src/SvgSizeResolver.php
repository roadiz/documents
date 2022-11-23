<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents;

use RZ\Roadiz\Documents\Models\DocumentInterface;

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
     * @return array|null [$x, $y, $width, $height]
     */
    protected function getViewBoxAttributes(): ?array
    {
        try {
            $viewBox = $this->getSvgNodeAttributes()->getNamedItem('viewBox');
            if (null !== $viewBox && $viewBox->textContent !== "") {
                return explode(' ', $viewBox->textContent);
            }
        } catch (\RuntimeException $exception) {
            return null;
        }

        return null;
    }

    /**
     * @param string $name
     * @return int|null
     */
    protected function getIntegerAttribute(string $name): ?int
    {
        try {
            $attribute = $this->getSvgNodeAttributes()->getNamedItem($name);
            if (
                null !== $attribute
                && $attribute->textContent !== ""
                && !\str_contains($attribute->textContent, '%')
            ) {
                return (int) $attribute->textContent;
            }
        } catch (\RuntimeException $exception) {
            return null;
        }
        return null;
    }

    /**
     * First, find width attr, then resolve width from viewBox.
     *
     * @return int
     */
    public function getWidth(): int
    {
        $widthAttr = $this->getIntegerAttribute('width');
        if (null !== $widthAttr) {
            return $widthAttr;
        }

        $viewBoxAttr = $this->getViewBoxAttributes();
        if (null !== $viewBoxAttr) {
            [$x, $y, $width, $height] = $viewBoxAttr;
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
        $heightAttr = $this->getIntegerAttribute('height');
        if (null !== $heightAttr) {
            return $heightAttr;
        }
        $viewBoxAttr = $this->getViewBoxAttributes();
        if (null !== $viewBoxAttr) {
            [$x, $y, $width, $height] = $viewBoxAttr;
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
