<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\MediaFinders;

use RZ\Roadiz\Documents\Exceptions\InvalidEmbedId;

abstract class AbstractDeezerEmbedFinder extends AbstractEmbedFinder
{
    protected static string $platform = 'deezer';
    // https://www.deezer.com/fr/playlist/9313425622
    protected static string $idPattern = '#^https?:\/\/(www.)?deezer\.com\/(?:\\w+/)?(?<type>track|playlist|artist|podcast|episode|album)\/(?<id>[a-zA-Z0-9]+)#';
    protected static string $realIdPattern = '#^(?<type>track|playlist|artist|podcast|episode|album)\/(?<id>[a-zA-Z0-9]+)$#';
    protected ?string $embedUrl;

    public static function supportEmbedUrl(string $embedUrl): bool
    {
        return str_starts_with($embedUrl, 'https://www.deezer.com');
    }

    public function isEmptyThumbnailAllowed(): bool
    {
        return true;
    }

    protected function validateEmbedId(string $embedId = ""): string
    {
        if (preg_match(static::$idPattern, $embedId, $matches) === 1) {
            return $embedId;
        }
        if (preg_match(static::$realIdPattern, $embedId, $matches) === 1) {
            return $embedId;
        }
        throw new InvalidEmbedId($embedId, static::$platform);
    }

    /**
     * @inheritDoc
     */
    public function getMediaFeed($search = null)
    {
        if (preg_match(static::$realIdPattern, $this->embedId)) {
            $url = 'https://www.deezer.com/fr/' . $this->embedId;
        } else {
            $url = $this->embedId;
        }
        $endpoint = "https://api.deezer.com/oembed";
        $query = [
            'url' => $url,
            'format' => 'json',
        ];

        return $this->downloadFeedFromAPI($endpoint . '?' . http_build_query($query));
    }

    /**
     * @inheritDoc
     */
    public function getFeed()
    {
        $feed = parent::getFeed();
        /*
         * We need to extract REAL embedId from oEmbed response, from the HTML field.
         */
        $this->embedUrl = $this->embedId;
        if (preg_match(static::$idPattern, $this->embedId, $matches)) {
            $this->embedId = $matches['type'] . '/' . $matches['id'];
        }

        return $feed;
    }

    public function getMediaTitle(): string
    {
        return isset($this->getFeed()['title']) ? $this->getFeed()['title'] : '';
    }

    public function getMediaDescription(): string
    {
        return isset($this->getFeed()['description']) ? $this->getFeed()['description'] : '';
    }

    public function getMediaCopyright(): string
    {
        return ($this->getFeed()['provider_name'] ?? '') . ' (' . ($this->getFeed()['provider_url'] ?? '') . ')';
    }

    public function getThumbnailURL(): string
    {
        return $this->getFeed()['thumbnail_url'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailName(string $pathinfo): string
    {
        if (preg_match('#\.(?<extension>[jpe?g|png|gif])$#', $pathinfo, $ext) === 1) {
            $pathinfo = '.' . $ext['extension'];
        } else {
            $pathinfo = '.jpg';
        }

        if (preg_match(static::$idPattern, $this->embedId, $matches) === 1) {
            return $matches['type'] . '_' . $matches['id'] . $pathinfo;
        }
        if (preg_match(static::$realIdPattern, $this->embedId, $matches) === 1) {
            return $matches['type'] . '_' . $matches['id'] . $pathinfo;
        }
        throw new InvalidEmbedId($this->embedId, static::$platform);
    }

    /**
     * Get embed media source URL.
     *
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = []): string
    {
        parent::getSource($options);

        $queryString = [
            'id' => $this->embedId
        ];

        if (key_exists('autoplay', $options)) {
            $queryString['autoplay'] = ((bool) $options['autoplay']) ? ('true') : ('false');
        }
        if ($options['width']) {
            $queryString['width'] = (int) $options['width'];
        }
        if ($options['height']) {
            $queryString['height'] = (int) $options['height'];
        }

        $queryString['loop'] = (int) $options['loop'];
        $queryString['controls'] = (int) $options['controls'];
        $queryString['fs'] = (int) $options['fullscreen'];
        $queryString['modestbranding'] = (int) $options['modestbranding'];
        $queryString['rel'] = (int) $options['rel'];
        $queryString['showinfo'] = (int) $options['showinfo'];
        $queryString['enablejsapi'] = (int) $options['enablejsapi'];
        $queryString['mute'] = (int) $options['muted'];

        if (preg_match(static::$realIdPattern, $this->embedId, $matches)) {
            $baseUri = 'https://widget.deezer.com/widget/auto/' . $this->embedId;
        } elseif (preg_match(static::$idPattern, $this->embedId, $matches)) {
            $baseUri = 'https://widget.deezer.com/widget/auto/' . $matches['type'] . '/' . $matches['id'];
        } else {
            $baseUri = 'https://widget.deezer.com/widget/auto/';
        }

        // https://widget.deezer.com/widget/dark/playlist/9313425622
        return $baseUri . '?' . http_build_query($queryString);
    }
}
