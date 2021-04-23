<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

interface SizeableInterface
{
    public function getImageWidth(): int;
    public function setImageWidth(int $imageWidth);
    public function getImageHeight(): int;
    public function setImageHeight(int $imageHeight);
    public function getImageRatio(): ?float;
}
