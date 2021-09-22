<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

interface AdvancedDocumentInterface extends DocumentInterface
{
    /**
     * @return int
     */
    public function getImageWidth(): int;

    /**
     * @param int $imageWidth
     *
     * @return AdvancedDocumentInterface
     */
    public function setImageWidth(int $imageWidth);

    /**
     * @return int
     */
    public function getImageHeight(): int;

    /**
     * @param int $imageHeight
     *
     * @return AdvancedDocumentInterface
     */
    public function setImageHeight(int $imageHeight);

    /**
     * @return float|null
     */
    public function getImageRatio(): ?float;

    /**
     * @return string|null
     */
    public function getImageAverageColor(): ?string;

    /**
     * @param  string|null $imageAverageColor
     * @return AdvancedDocumentInterface
     */
    public function setImageAverageColor(?string $imageAverageColor);

    /**
     * @return int|null
     */
    public function getFilesize(): ?int;

    /**
     * @param  int|null $filesize
     * @return AdvancedDocumentInterface
     */
    public function setFilesize(?int $filesize);
}
