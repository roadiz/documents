<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Simple document implementation for tests purposes.
 *
 * @package RZ\Roadiz\Core\Models
 */
class SimpleDocument extends AbstractDocument
{
    private string $filename = '';
    private string $folder = '';
    private ?string $embedId = null;
    private ?string $embedPlatform = null;
    private ?string $mimeType = null;
    private bool $private = false;
    private bool $raw = false;
    private Collection $folders;
    private ?DocumentInterface $rawDocument = null;
    private ?DocumentInterface $downscaledDocument = null;

    public function __construct()
    {
        parent::__construct();
        $this->folders = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return DocumentInterface
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * @param string|null $mimeType
     *
     * @return SimpleDocument
     */
    public function setMimeType(?string $mimeType): SimpleDocument
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * @param string $folder
     *
     * @return SimpleDocument
     */
    public function setFolder(string $folder): SimpleDocument
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmbedId(): ?string
    {
        return $this->embedId;
    }

    /**
     * @param string|null $embedId
     *
     * @return SimpleDocument
     */
    public function setEmbedId(?string $embedId): SimpleDocument
    {
        $this->embedId = $embedId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmbedPlatform(): ?string
    {
        return $this->embedPlatform;
    }

    /**
     * @param string|null $embedPlatform
     *
     * @return SimpleDocument
     */
    public function setEmbedPlatform(?string $embedPlatform): SimpleDocument
    {
        $this->embedPlatform = $embedPlatform;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->private;
    }

    /**
     * @param bool $private
     *
     * @return SimpleDocument
     */
    public function setPrivate(bool $private): SimpleDocument
    {
        $this->private = $private;

        return $this;
    }

    /**
     * @return DocumentInterface|null
     */
    public function getRawDocument(): ?DocumentInterface
    {
        return $this->rawDocument;
    }

    /**
     * @param DocumentInterface|null $rawDocument
     *
     * @return SimpleDocument
     */
    public function setRawDocument(?DocumentInterface $rawDocument = null): SimpleDocument
    {
        $this->rawDocument = $rawDocument;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRaw(): bool
    {
        return $this->raw;
    }

    /**
     * @param bool $raw
     *
     * @return SimpleDocument
     */
    public function setRaw(bool $raw): SimpleDocument
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * @return DocumentInterface|null
     */
    public function getDownscaledDocument(): ?DocumentInterface
    {
        return $this->downscaledDocument;
    }

    /**
     * @param DocumentInterface|null $downscaledDocument
     *
     * @return SimpleDocument
     */
    public function setDownscaledDocument(?DocumentInterface $downscaledDocument): SimpleDocument
    {
        $this->downscaledDocument = $downscaledDocument;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    /**
     * @param Collection $folders
     *
     * @return SimpleDocument
     */
    public function setFolders(Collection $folders): SimpleDocument
    {
        $this->folders = $folders;
        return $this;
    }

    /**
     * @param FolderInterface $folder
     *
     * @return $this|DocumentInterface
     */
    public function addFolder(FolderInterface $folder)
    {
        $this->folders->add($folder);
        return $this;
    }

    /**
     * @param FolderInterface $folder
     *
     * @return $this|DocumentInterface
     */
    public function removeFolder(FolderInterface $folder)
    {
        $this->folders->removeElement($folder);
        return $this;
    }

    public function __toString()
    {
        return $this->getFilename();
    }
}
