<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation as SymfonySerializer;

/**
 * @package RZ\Roadiz\Core\Models
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractDocument extends AbstractDateTimed implements DocumentInterface
{
    /**
     * Associate mime type to simple types.
     *
     * - code
     * - image
     * - word
     * - video
     * - audio
     * - pdf
     * - archive
     * - excel
     * - powerpoint
     * - font
     *
     * @var array<string, string>
     * @internal
     * @SymfonySerializer\Ignore()
     */
    protected static array $mimeToIcon = [
        'text/html' => 'code',
        'application/javascript' => 'code',
        'text/css' => 'code',
        'text/rtf' => 'word',
        'text/xml' => 'code',
        'image/png' => 'image',
        'image/jpeg' => 'image',
        'image/gif' => 'image',
        'image/tiff' => 'image',
        'image/webp' => 'image',
        'image/avif' => 'image',
        'image/vnd.microsoft.icon' => 'image',
        'image/x-icon' => 'image',
        'application/pdf' => 'pdf',
        // Audio types
        'audio/mpeg' => 'audio',
        'audio/x-m4a' => 'audio',
        'audio/x-wav' => 'audio',
        'audio/wav' => 'audio',
        'audio/aac' => 'audio',
        'audio/mp4' => 'audio',
        'audio/webm' => 'audio',
        'audio/ogg' => 'audio',
        'audio/vorbis' => 'audio',
        'audio/ac3' => 'audio',
        'audio/x-matroska' => 'audio',
        // Video types
        'application/ogg' => 'video',
        'video/ogg' => 'video',
        'video/webm' => 'video',
        'video/mpeg' => 'video',
        'video/mp4' => 'video',
        'video/x-m4v' => 'video',
        'video/quicktime' => 'video',
        'video/x-flv' => 'video',
        'video/3gpp' => 'video',
        'video/3gpp2' => 'video',
        'video/3gpp-tt' => 'video',
        'video/VP8' => 'video',
        'video/x-matroska' => 'video',
        // Epub type
        'application/epub+zip' => 'epub',
        // Archives types
        'application/gzip' => 'archive',
        'application/zip' => 'archive',
        'application/x-bzip2' => 'archive',
        'application/x-tar' => 'archive',
        'application/x-7z-compressed' => 'archive',
        'application/x-apple-diskimage' => 'archive',
        'application/x-rar-compressed' => 'archive',
        // Office types
        'application/msword' => 'word',
        'application/vnd.ms-excel' => 'excel',
        'application/vnd.ms-office' => 'excel',
        'application/vnd.ms-powerpoint' => 'powerpoint',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
        'application/vnd.oasis.opendocument.text ' => 'word',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'excel',
        'application/vnd.oasis.opendocument.spreadsheet' => 'excel',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 'powerpoint',
        'application/vnd.oasis.opendocument.presentation' => 'powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'powerpoint',
        // Fonts types
        'image/svg+xml' => 'font',
        'application/x-font-ttf' => 'font',
        'application/x-font-truetype' => 'font',
        'application/x-font-opentype' => 'font',
        'application/font-woff' => 'font',
        'application/vnd.ms-fontobject' => 'font',
        'font/opentype' => 'font',
        'font/ttf' => 'font',
    ];

    /**
     * @var string[] Processable file mime type by GD or Imagick.
     * @internal
     * @SymfonySerializer\Ignore()
     */
    protected static array $processableMimeTypes = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/tiff',
        'image/webp',
        'image/avif',
    ];

    /**
     * Get short type name for current document Mime type.
     *
     * @return string
     * @SymfonySerializer\Ignore()
     */
    public function getShortType(): string
    {
        if (null !== $this->getMimeType() && isset(static::$mimeToIcon[$this->getMimeType()])) {
            return static::$mimeToIcon[$this->getMimeType()];
        } else {
            return 'unknown';
        }
    }

    /**
     * Get short Mime type.
     *
     * @return string
     * @SymfonySerializer\Ignore()
     */
    public function getShortMimeType(): string
    {
        if (!empty($this->getMimeType())) {
            $mime = explode('/', $this->getMimeType());
            return $mime[count($mime) - 1];
        }
        return 'unknown';
    }

    /**
     * Is current document an image.
     *
     * @return bool
     * @SymfonySerializer\Ignore()
     */
    public function isImage(): bool
    {
        return static::getShortType() === 'image';
    }

    /**
     * Is current document a vector SVG file.
     *
     * @return bool
     * @SymfonySerializer\Ignore()
     */
    public function isSvg(): bool
    {
        return $this->getMimeType() === 'image/svg+xml' || $this->getMimeType() === 'image/svg';
    }

    /**
     * Is current document a video.
     *
     * @return bool
     * @SymfonySerializer\Ignore()
     */
    public function isVideo(): bool
    {
        return static::getShortType() === 'video';
    }

    /**
     * Is current document an audio file.
     *
     * @return bool
     * @SymfonySerializer\Ignore()
     */
    public function isAudio(): bool
    {
        return static::getShortType() === 'audio';
    }

    /**
     * Is current document a PDF file.
     *
     * @return bool
     * @SymfonySerializer\Ignore()
     */
    public function isPdf(): bool
    {
        return static::getShortType() === 'pdf';
    }

    /**
     * @return bool
     * @SymfonySerializer\Ignore()
     */
    public function isWebp(): bool
    {
        return $this->getMimeType() === 'image/webp';
    }

    /**
     * @deprecated Use getRelativePath instead, naming is better.
     * @SymfonySerializer\Ignore()
     * @return     string|null
     */
    public function getRelativeUrl(): ?string
    {
        return $this->getRelativePath();
    }

    /**
     * @return null|string
     * @Serializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @SymfonySerializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @Serializer\Type("string")
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("relativePath")
     * @SymfonySerializer\SerializedName("relativePath")
     */
    public function getRelativePath(): ?string
    {
        if ($this->isLocal()) {
            return $this->getFolder() . '/' . $this->getFilename();
        } else {
            return null;
        }
    }

    /**
     * Tells if current document has embed media information.
     *
     * @return bool
     * @SymfonySerializer\Ignore()
     */
    public function isEmbed(): bool
    {
        return (!empty($this->getEmbedId()) && !empty($this->getEmbedPlatform()));
    }

    public function __construct()
    {
        $this->setFolder(substr(hash("crc32b", date('YmdHi')), 0, 12));
        $this->initAbstractDateTimed();
    }

    /**
     * @inheritDoc
     * @Serializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @SymfonySerializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @Serializer\Type("bool")
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("processable")
     * @SymfonySerializer\SerializedName("processable")
     */
    public function isProcessable(): bool
    {
        if ($this->isImage() && in_array($this->getMimeType(), static::$processableMimeTypes)) {
            return true;
        }

        return false;
    }

    /**
     * @Serializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @SymfonySerializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @Serializer\Type("string")
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("alt")
     * @SymfonySerializer\SerializedName("alt")
     */
    public function getAlternativeText(): string
    {
        return $this->getFilename();
    }

    /**
     * Return false if no local file is linked to document. i.e no filename, no folder
     *
     * @return bool
     * @SymfonySerializer\Ignore()
     */
    public function isLocal(): bool
    {
        return $this->getFilename() !== '' && $this->getFolder() !== '';
    }
}
