<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Viewers;

use enshrined\svgSanitize\Sanitizer;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class SvgDocumentViewer
{
    protected string $imagePath;
    protected array $attributes;
    protected bool $asObject = false;
    protected string $imageUrl;

    /**
     * @var string[]
     */
    public static array $allowedAttributes = [
        'width',
        'height',
        'identifier',
        'class',
    ];

    /**
     * @param string  $imagePath
     * @param array   $attributes
     * @param boolean $asObject Default false
     * @param string  $imageUrl Only needed if you set $asObject to true.
     */
    public function __construct(
        string $imagePath,
        array $attributes = [],
        bool $asObject = false,
        string $imageUrl = ""
    ) {
        $this->imagePath = $imagePath;
        $this->imageUrl = $imageUrl;
        $this->attributes = $attributes;
        $this->asObject = $asObject;
    }

    /**
     * Get SVG string to be used inside HTML content.
     *
     * @return string
     */
    public function getContent(): string
    {
        if (false === $this->asObject) {
            return $this->getInlineSvg();
        } else {
            return $this->getObjectSvg();
        }
    }

    /**
     * @return array
     */
    protected function getAllowedAttributes(): array
    {
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
            if (in_array($key, static::$allowedAttributes)) {
                if ($key === 'identifier') {
                    $attributes['id'] = $value;
                } else {
                    $attributes[$key] = $value;
                }
            }
        }
        return $attributes;
    }

    /**
     * @return string
     */
    protected function getInlineSvg(): string
    {
        if (!file_exists($this->imagePath)) {
            throw new FileNotFoundException('SVG file does not exist: ' . $this->imagePath);
        }
        // Create a new sanitizer instance
        $sanitizer = new Sanitizer();
        $sanitizer->minify(true);

        // Load the dirty svg
        $dirtySVG = file_get_contents($this->imagePath);
        if (false === $dirtySVG) {
            throw new \RuntimeException($this->imagePath . ' file is not readable.');
        }
        /** @var string|false $cleanSVG */
        $cleanSVG = $sanitizer->sanitize($dirtySVG);
        if (false !== $cleanSVG) {
            // Pass it to the sanitizer and get it back clean
            return $this->injectAttributes($cleanSVG);
        }
        return $dirtySVG;
    }

    /**
     * @param string $svg
     * @return string
     */
    protected function injectAttributes($svg): string
    {
        $attributes = $this->getAllowedAttributes();
        if (count($attributes) > 0) {
            $xml = new \SimpleXMLElement($svg);
            $xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
            $xml->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');
            $xml->registerXPathNamespace('a', 'http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/');
            $xml->registerXPathNamespace('ns1', 'http://ns.adobe.com/Flows/1.0/');
            $xml->registerXPathNamespace('ns0', 'http://ns.adobe.com/SaveForWeb/1.0/');
            $xml->registerXPathNamespace('ns', 'http://ns.adobe.com/Variables/1.0/');
            $xml->registerXPathNamespace('i', 'http://ns.adobe.com/AdobeIllustrator/10.0/');
            $xml->registerXPathNamespace('x', 'http://ns.adobe.com/Extensibility/1.0/');
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
            $xml->registerXPathNamespace('cc', 'http://creativecommons.org/ns#');
            $xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
            $xml->registerXPathNamespace('sodipodi', 'http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd');
            $xml->registerXPathNamespace('inkscape', 'http://www.inkscape.org/namespaces/inkscape');

            foreach ($attributes as $key => $value) {
                if (isset($xml->attributes()->$key)) {
                    $xml->attributes()->$key = (string) $value;
                } else {
                    $xml->addAttribute($key, (string) $value);
                }
            }
            $svg = $xml->asXML();
        }
        if (false === $svg) {
            throw new \RuntimeException('Cannot inject attributes into SVG');
        }
        $svg = preg_replace('#^\<\?xml[^\?]+\?\>#', '', (string) $svg) ?? '';

        return $svg;
    }

    /**
     * @return string
     * @deprecated  Use SvgRenderer to render HTML object.
     */
    protected function getObjectSvg(): string
    {
        $attributes = $this->getAllowedAttributes();
        $attributes['type'] = 'image/svg+xml';
        $attributes['data'] = $this->imageUrl;

        if (isset($attributes['alt'])) {
            unset($attributes['alt']);
        }

        $attrs = [];
        foreach ($attributes as $key => $value) {
            $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
        }

        return '<object ' . implode(' ', $attrs) . '></object>';
    }
}
