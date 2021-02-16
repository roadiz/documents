<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;

class ChainRenderer implements RendererInterface
{
    /**
     * @var array<RendererInterface>
     */
    private array $renderers;

    /**
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

    /**
     * @param RendererInterface $renderer
     *
     * @return $this
     */
    public function addRenderer(RendererInterface $renderer)
    {
        $this->renderers[] = $renderer;
        return $this;
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
