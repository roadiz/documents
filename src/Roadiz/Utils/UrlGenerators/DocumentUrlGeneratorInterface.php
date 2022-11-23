<?php

declare(strict_types=1);

namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Core\Models\DocumentInterface;

interface DocumentUrlGeneratorInterface
{
    /**
     * @param bool $absolute
     *
     * @return string
     */
    public function getUrl(bool $absolute = false): string;

    /**
     * @param DocumentInterface $document
     *
     * @return $this
     */
    public function setDocument(DocumentInterface $document): static;

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options = []): static;
}
