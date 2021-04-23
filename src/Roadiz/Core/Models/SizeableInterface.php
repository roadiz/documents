<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

interface SizeableInterface
{
    /**
     * @return int
     */
    public function getImageWidth(): int;

    /**
     * @param int $imageWidth
     * @return static
     */
    public function setImageWidth(int $imageWidth);

    /**
     * @return int
     */
    public function getImageHeight(): int;

    /**
     * @param int $imageHeight
     * @return static
     */
    public function setImageHeight(int $imageHeight);

    /**
     * @return float|null
     */
    public function getImageRatio(): ?float;
}
