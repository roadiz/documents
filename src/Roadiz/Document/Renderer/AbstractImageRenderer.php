<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;

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
            $srcset = [];
            foreach ($options['srcset'] as $set) {
                if (isset($set['format']) && isset($set['rule'])) {
                    $this->documentUrlGenerator->setOptions($set['format']);
                    $this->documentUrlGenerator->setDocument($document);
                    $path = $this->documentUrlGenerator->getUrl($options['absolute']);
                    if ($convertToWebP) {
                        $path .= '.webp';
                    }
                    $srcset[] = $path . ' ' . $set['rule'];
                }
            }
            return implode(', ', $srcset);
        }
        return null;
    }
}
