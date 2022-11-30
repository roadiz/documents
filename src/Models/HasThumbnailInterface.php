<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\Models;

use Doctrine\Common\Collections\Collection;

interface HasThumbnailInterface
{
    /**
     * @return HasThumbnailInterface|null
     */
    public function getOriginal(): ?HasThumbnailInterface;

    /**
     * @param HasThumbnailInterface|null $original
     * @return $this
     */
    public function setOriginal(?HasThumbnailInterface $original): static;

    /**
     * @return Collection<static>
     */
    public function getThumbnails(): Collection;

    /**
     * @param Collection<static> $thumbnails
     * @return $this
     */
    public function setThumbnails(Collection $thumbnails): static;

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