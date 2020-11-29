<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class AbstractDocument
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
     * @var array
     */
    protected static $mimeToIcon = [
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
     * @var array Processable file mime type by GD or Imagick.
     */
    protected static $processableMimeTypes = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/tiff',
        'image/webp',
    ];

    /**
     * Get short type name for current document Mime type.
     *
     * @return string
     */
    public function getShortType()
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
     */
    public function getShortMimeType()
    {
        if (null !== $this->getMimeType()) {
            $mime = explode('/', $this->getMimeType() ?? '');
            return $mime[count($mime) - 1];
        }
        return 'unknown';
    }

    /**
     * Is current document an image.
     *
     * @return boolean
     */
    public function isImage()
    {
        return static::getShortType() === 'image';
    }

    /**
     * Is current document a vector SVG file.
     *
     * @return boolean
     */
    public function isSvg()
    {
        return $this->getMimeType() === 'image/svg+xml' || $this->getMimeType() === 'image/svg';
    }

    /**
     * Is current document a video.
     *
     * @return boolean
     */
    public function isVideo()
    {
        return static::getShortType() === 'video';
    }

    /**
     * Is current document an audio file.
     *
     * @return boolean
     */
    public function isAudio()
    {
        return static::getShortType() === 'audio';
    }

    /**
     * Is current document a PDF file.
     *
     * @return bool
     */
    public function isPdf()
    {
        return static::getShortType() === 'pdf';
    }

    /**
     * @return bool
     */
    public function isWebp()
    {
        return $this->getMimeType() === 'image/webp';
    }

    /**
     * @deprecated Use getRelativePath instead, naming is better.
     * @return string|null
     */
    public function getRelativeUrl()
    {
        return $this->getRelativePath();
    }

    /**
     * @return null|string
     * @Serializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @Serializer\Type("string")
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("relativePath")
     */
    public function getRelativePath()
    {
        if (!empty($this->getFilename())) {
            return $this->getFolder() . '/' . $this->getFilename();
        } else {
            return null;
        }
    }

    /**
     * Tells if current document has embed media information.
     *
     * @return boolean
     */
    public function isEmbed()
    {
        return (!empty($this->getEmbedId()) && !empty($this->getEmbedPlatform()));
    }

    /**
     * AbstractDocument constructor.
     */
    public function __construct()
    {
        $this->setFolder(substr(hash("crc32b", date('YmdHi')), 0, 12));
    }

    /**
     * @inheritDoc
     * @Serializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @Serializer\Type("bool")
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("processable")
     */
    public function isProcessable()
    {
        if ($this->isImage() && in_array($this->getMimeType(), static::$processableMimeTypes)) {
            return true;
        }

        return false;
    }

    /**
     * @Serializer\Groups({"document", "document_display", "nodes_sources", "tag", "attribute"})
     * @Serializer\Type("string")
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("alt")
     */
    public function getAlternativeText(): string
    {
        return $this->getFilename();
    }
}
