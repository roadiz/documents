<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

use Doctrine\Common\Collections\Collection;

interface DocumentInterface
{
    public function getFilename(): string;

    /**
     * @param  string $filename
     * @return DocumentInterface
     */
    public function setFilename(string $filename);

    /**
     * @return string|null
     */
    public function getMimeType(): ?string;

    /**
     * @param  string|null $mimeType
     * @return DocumentInterface
     */
    public function setMimeType(?string $mimeType);

    /**
     * Get short type name for current document Mime type.
     *
     * @return string
     */
    public function getShortType(): string;

    /**
     * Get short Mime type.
     *
     * @return string
     */
    public function getShortMimeType(): string;

    /**
     * Is current document an image.
     *
     * @return bool
     */
    public function isImage(): bool;

    /**
     * Is current document a vector SVG file.
     *
     * @return bool
     */
    public function isSvg(): bool;

    /**
     * Is current document a Webp image.
     *
     * @return bool
     */
    public function isWebp(): bool;

    /**
     * Is current document a video.
     *
     * @return bool
     */
    public function isVideo(): bool;

    /**
     * Is current document an audio file.
     *
     * @return bool
     */
    public function isAudio(): bool;

    /**
     * Is current document a PDF file.
     *
     * @return bool
     */
    public function isPdf(): bool;

    public function getFolder(): string;

    /**
     * @param  string $folder
     * @return DocumentInterface
     */
    public function setFolder(string $folder);

    public function getRelativePath(): ?string;

    public function getEmbedId(): ?string;

    /**
     * @param  string|null $embedId
     * @return DocumentInterface
     */
    public function setEmbedId(?string $embedId);

    public function getEmbedPlatform(): ?string;

    /**
     * @param  string|null $embedPlatform
     * @return DocumentInterface
     */
    public function setEmbedPlatform(?string $embedPlatform);

    /**
     * Tells if current document has embed media information.
     *
     * @return bool
     */
    public function isEmbed(): bool;

    /**
     * @return bool
     */
    public function isPrivate(): bool;

    /**
     * @param  bool $private
     * @return DocumentInterface
     */
    public function setPrivate(bool $private);

    public function getRawDocument(): ?DocumentInterface;

    /**
     * @param  DocumentInterface|null $rawDocument the raw document
     * @return DocumentInterface
     */
    public function setRawDocument(?DocumentInterface $rawDocument = null);

    /**
     * Is document a raw one.
     *
     * @return bool
     */
    public function isRaw(): bool;

    /**
     * @param  boolean $raw the raw
     * @return DocumentInterface
     */
    public function setRaw(bool $raw);

    /**
     * Gets the downscaledDocument.
     *
     * @return DocumentInterface|null
     */
    public function getDownscaledDocument(): ?DocumentInterface;

    /**
     * @return Collection<FolderInterface>
     */
    public function getFolders(): Collection;

    /**
     * @param  FolderInterface $folder
     * @return DocumentInterface
     */
    public function addFolder(FolderInterface $folder);

    /**
     * @param  FolderInterface $folder
     * @return DocumentInterface
     */
    public function removeFolder(FolderInterface $folder);

    /**
     * Return false if no local file is linked to document. i.e no filename, no folder
     *
     * @return bool
     */
    public function isLocal(): bool;
    /**
     * Return true if current document can be processed by intervention-image (GD, Imagick…).
     *
     * @return bool
     */
    public function isProcessable(): bool;

    public function getAlternativeText(): string;

    public function __toString();
}
