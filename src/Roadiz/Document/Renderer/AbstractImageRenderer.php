<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Utils\Document\UrlOptionsResolver;

abstract class AbstractImageRenderer extends AbstractRenderer
{
    /**
     * @param array $options
     * @return string|null
     */
    protected function parseSizes(array $options = []): ?string
    {
        if (count($options['sizes']) > 0) {
            return implode(', ', $options['sizes']);
        }

        return null;
    }

    /**
     * @param DocumentInterface $document
     * @param array             $options
     * @param bool              $convertToWebP
     *
     * @return string|null
     */
    protected function parseSrcSet(DocumentInterface $document, array $options = [], $convertToWebP = false): ?string
    {
        if (count($options['srcset']) > 0) {
            return $this->parseSrcSetInner($document, $options['srcset'], $convertToWebP, $options['absolute']);
        }
        return null;
    }

    /**
     * @param DocumentInterface $document
     * @param array             $srcSetArray
     * @param bool              $convertToWebP
     * @param bool              $absolute
     *
     * @return string
     */
    protected function parseSrcSetInner(
        DocumentInterface $document,
        array $srcSetArray = [],
        $convertToWebP = false,
        $absolute = false
    ): string {
        $output = [];
        foreach ($srcSetArray as $set) {
            if (isset($set['format']) && isset($set['rule'])) {
                $resolver = new UrlOptionsResolver();
                $this->documentUrlGenerator->setOptions($resolver->resolve($set['format']));
                $this->documentUrlGenerator->setDocument($document);
                $path = $this->documentUrlGenerator->getUrl($absolute);
                if ($convertToWebP) {
                    $path .= '.webp';
                }
                $output[] = $path . ' ' . $set['rule'];
            }
        }
        return implode(', ', $output);
    }
}
