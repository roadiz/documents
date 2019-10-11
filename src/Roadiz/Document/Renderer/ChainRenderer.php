<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;

class ChainRenderer implements RendererInterface
{
    /**
     * @var array<RendererInterface>
     */
    private $renderers;

    /**
     * ChainRenderer constructor.
     *
     * @param array $renderers
     */
    public function __construct(array $renderers)
    {
        /** @var RendererInterface $renderer */
        foreach ($renderers as $renderer) {
            if (!($renderer instanceof RendererInterface)) {
                throw new \InvalidArgumentException('Document Renderer must implement RendererInterface');
            }
        }
        $this->renderers = $renderers;
    }

    public function supports(DocumentInterface $document, array $options): bool
    {
        return true;
    }

    public function render(DocumentInterface $document, array $options): string
    {
        /** @var RendererInterface $renderer */
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($document, $options)) {
                return $renderer->render($document, $options);
            }
        }

        return '<p>Document could not be rendered.</p>';
    }
}
