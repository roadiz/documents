<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

interface EmbedFinderInterface
{
    /**
     * @param array $options
     *
     * @return string
     */
    public function getIFrame(array &$options = []): string;

    /**
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = []): string;
}
