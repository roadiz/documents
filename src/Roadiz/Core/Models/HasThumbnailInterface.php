<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

use Doctrine\Common\Collections\Collection;

interface HasThumbnailInterface
{
    /**
     * @return HasThumbnailInterface|null
     */
    public function getOriginal(): ?HasThumbnailInterface;

    /**
     * @param HasThumbnailInterface $hasThumbnail
     *
     * @return self
     */
    public function setOriginal(HasThumbnailInterface $hasThumbnail);

    /**
     * @return Collection
     */
    public function getThumbnails(): Collection;

    /**
     * @param Collection $thumbnails
     *
     * @return self
     */
    public function setThumbnails(Collection $thumbnails);

    /**
     * @return bool
     */
    public function isThumbnail(): bool;

    /**
     * @return bool
     */
    public function hasThumbnails(): bool;

    /**
     * @return bool
     */
    public function needsThumbnail(): bool;
}
