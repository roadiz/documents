<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents;

use DOMNamedNodeMap;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RZ\Roadiz\Documents\Models\DocumentInterface;

final class SvgSizeResolver
{
    private DocumentInterface $document;
    private ?\DOMDocument $xmlDocument = null;
    private ?\DOMNode $svgNode = null;
    private FilesystemOperator $documentsStorage;

    public function __construct(DocumentInterface $document, FilesystemOperator $documentsStorage)
    {
        $this->document = $document;
        $this->documentsStorage = $documentsStorage;
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
        /** @var DOMNamedNodeMap|null $attributes */
        $attributes = $this->getSvgNode()->attributes;
        if (null === $attributes) {
            throw new \RuntimeException('SVG tag <svg> does not contain any attribute');
        }

        return $attributes;
    }

    /**
     * @throws FilesystemException
     */
    private function getDOMDocument(): \DOMDocument
    {
        if (null === $this->xmlDocument) {
            $mountPath = $this->document->getMountPath();
            if (null === $mountPath) {
                throw new \RuntimeException('SVG does not have file.');
            }
            $this->xmlDocument = new \DOMDocument();
            $svgSource = $this->documentsStorage->read($mountPath);
            if (false === $this->xmlDocument->loadXML($svgSource)) {
                throw new \RuntimeException(sprintf('SVG (%s) could not be loaded.', $mountPath));
            }
        }
        return $this->xmlDocument;
    }
}
