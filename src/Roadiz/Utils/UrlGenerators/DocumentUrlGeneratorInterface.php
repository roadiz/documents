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
     * @return mixed
     */
    public function setDocument(DocumentInterface $document);

    /**
     * @param array $options
     *
     * @return mixed
     */
    public function setOptions(array $options = []);
}
