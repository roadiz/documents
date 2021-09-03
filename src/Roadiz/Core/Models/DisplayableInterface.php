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
     * @param  string|null $imageAverageColor
     * @return self
     */
    public function setImageAverageColor(?string $imageAverageColor);
}
