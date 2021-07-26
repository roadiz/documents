<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

interface DisplayableInterface
{
    /**
     * @return string|null
     */
    public function getImageAverageColor(): ?string;

    /**
     * @param string|null $imageAverageColor
     */
    public function setImageAverageColor(?string $imageAverageColor);
}
