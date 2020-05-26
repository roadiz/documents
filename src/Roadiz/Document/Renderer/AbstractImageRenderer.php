<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\AdvancedDocumentInterface;
use RZ\Roadiz\Utils\Document\UrlOptionsResolver;
use RZ\Roadiz\Utils\MediaFinders\EmbedFinderFactory;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGeneratorInterface;
use Twig\Environment;

abstract class AbstractImageRenderer extends AbstractRenderer
{
    /**
     * @var EmbedFinderFactory
     */
    protected $embedFinderFactory;

    public function __construct(
        EmbedFinderFactory $embedFinderFactory,
        Environment $templating,
        DocumentUrlGeneratorInterface $documentUrlGenerator,
        string $templateBasePath = 'documents'
    ) {
        parent::__construct($templating, $documentUrlGenerator, $templateBasePath);
        $this->embedFinderFactory = $embedFinderFactory;
    }

    public function supports(DocumentInterface $document, array $options): bool
    {
        return $document->isImage() && !$this->isEmbeddable($document, $options);
    }

    public function isEmbeddable(DocumentInterface $document, array $options): bool
    {
        return isset($options['embed']) &&
            $options['embed'] === true &&
            null !== $document->getEmbedPlatform() &&
            $this->embedFinderFactory->supports($document->getEmbedPlatform());
    }

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

    /**
     * @param string $hexColor
     * @param int    $width
     * @param int    $height
     *
     * @return string
     */
    protected function createTransparentDataURI(string $hexColor, int $width = 1, int $height = 1)
    {
        [$r, $g, $b] = \sscanf($hexColor, "#%02x%02x%02x");
        $im = \imageCreateTrueColor($width, $height);
        if ($im) {
            \imageFill($im, 0, 0, \imageColorAllocate($im, $r, $g, $b));
            \ob_start();
            \imagejpeg($im, null, 30);
            $img = \ob_get_contents();
            \ob_end_clean();
            if ($img) {
                return 'data:image/jpeg;base64,' . \base64_encode($img);
            }
        }
        throw new \RuntimeException('Cannot generate imageCreateTrueColor');
    }

    /**
     * @param DocumentInterface $document
     * @param array             $options
     * @param array             $assignation
     */
    protected function additionalAssignation(DocumentInterface $document, array $options, array &$assignation): void
    {
        if ($document instanceof AdvancedDocumentInterface) {
            if (null !== $document->getImageRatio()) {
                $assignation['ratio'] = $document->getImageRatio();
            }
            if (null !== $document->getImageAverageColor() &&
                $document->getImageAverageColor() !== '#ffffff' &&
                $document->getImageAverageColor() !== '#000000') {
                $assignation['averageColor'] = $document->getImageAverageColor();
            }
            if ($options['blurredFallback'] === true) {
                if (!empty($options['fit'])) {
                    // Both Fit and Width cannot be explicitly set
                    // need to revert on Crop
                    $options['crop'] = $options['fit'];
                    unset($options['fit']);
                }
                if (!empty($options['height'])) {
                    unset($options['height']);
                }
                $assignation['fallback'] = $this->getSource($document, array_merge($options, [
                    'quality' => 10,
                    'width' => 60
                ]));
            }
        }
    }
}
