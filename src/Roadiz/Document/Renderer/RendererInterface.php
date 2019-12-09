<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document\Renderer;

use RZ\Roadiz\Core\Models\DocumentInterface;

interface RendererInterface
{
    /**
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return bool
     */
    public function supports(DocumentInterface $document, array $options): bool;

    /**
     * @param DocumentInterface $document
     * @param array             $options
     *
     * @return string
     */
    public function render(DocumentInterface $document, array $options): string;
}
