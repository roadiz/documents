<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\Models;

use Doctrine\Common\Collections\Collection;

interface HasThumbnailInterface
{
    public function getOriginal(): ?HasThumbnailInterface;

    /**
     * @return $this
     */
    public function setOriginal(?HasThumbnailInterface $original): static;

    /**
     * @return Collection<int, DocumentInterface>
     */
    public function getThumbnails(): Collection;

    /**
     * @param Collection<int, DocumentInterface> $thumbnails
     *
     * @return $this
     */
    public function setThumbnails(Collection $thumbnails): static;

    public function isThumbnail(): bool;

    public function hasThumbnails(): bool;

    public function needsThumbnail(): bool;
}
