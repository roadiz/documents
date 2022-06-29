<?php

declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Core\Models\HasThumbnailInterface;

/**
 * Fallback document render to its first thumbnail.
 *
 * @package RZ\Roadiz\Document\Renderer
 */
class ThumbnailRenderer implements RendererInterface
{
    protected ?ChainRenderer $chainRenderer = null;

    /**
     * @param ChainRenderer|null $chainRenderer
     */
    public function __construct(?ChainRenderer $chainRenderer = null)
    {
        $this->chainRenderer = $chainRenderer;
    }

    /**
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return bool
     */
    public function supports(DocumentInterface $document, array $options): bool
    {
        return null !== $this->chainRenderer &&
            (!key_exists('embed', $options) ||
            $options['embed'] !== true) &&
            $document instanceof HasThumbnailInterface &&
            $document->hasThumbnails() &&
            false !== $document->getThumbnails()->first();
    }

    /**
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return string
     */
    public function render(DocumentInterface $document, array $options): string
    {
        if (
            null !== $this->chainRenderer &&
            $document instanceof HasThumbnailInterface &&
            false !== $thumbnail = $document->getThumbnails()->first()
        ) {
            return $this->chainRenderer->render($thumbnail, $options);
        }
        return '';
    }
}
