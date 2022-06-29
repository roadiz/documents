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
     * @param HasThumbnailInterface $original
     * @return self
     */
    public function setOriginal(?HasThumbnailInterface $original);

    /**
     * @return Collection<int,DocumentInterface>
     */
    public function getThumbnails(): Collection;

    /**
     * @param Collection<int,DocumentInterface> $thumbnails
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
