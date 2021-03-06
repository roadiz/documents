<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;

abstract class AbstractTwitchEmbedFinder extends AbstractEmbedFinder
{
    protected static string $platform = 'twitch';
    protected static string $idPattern = '#^https\:\/\/(www\.)?twitch\.tv\/videos\/(?<id>[0-9]+)#';

    /**
     * @inheritDoc
     */
    protected function validateEmbedId(string $embedId = ""): string
    {
        if (preg_match(static::$idPattern, $embedId, $matches) === 1) {
            return $embedId;
        }
        throw new InvalidEmbedId($embedId, static::$platform);
    }

    /**
     * @inheritDoc
     */
    public function getMediaFeed($search = null)
    {
        $endpoint = "https://api.twitch.tv/v4/oembed";
        $query = [
            'url' => $this->embedId,
        ];

        return $this->downloadFeedFromAPI($endpoint . '?' . http_build_query($query));
    }

    public function getMediaTitle(): string
    {
        return $this->getFeed()['title'] ?? '';
    }

    public function getMediaDescription(): string
    {
        return $this->getFeed()['description'] ?? '';
    }

    public function getMediaCopyright(): string
    {
        return ($this->getFeed()['author_name'] ?? '') . ' - ' . ($this->getFeed()['provider_name'] ?? '') . ' (' . ($this->getFeed()['author_url'] ?? ''). ')';
    }

    public function getThumbnailURL(): string
    {
        return $this->getFeed()['thumbnail_url'] ?? '';
    }

    public function getThumbnailName(string $pathinfo): string
    {
        if (preg_match('#\.(?<extension>[jpe?g|png|gif])$#', $pathinfo, $ext) === 1) {
            $pathinfo = '.' . $ext['extension'];
        } else {
            $pathinfo = '.jpg';
        }
        if (preg_match(static::$idPattern, $this->embedId, $matches) === 1) {
            return 'twitch_' . $matches['id'] . $pathinfo;
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

        if (preg_match(static::$idPattern, $this->embedId, $matches)) {
            $queryString = [
                'video' => $matches['id'],
                'branding' => 0,
            ];

            if ($options['autoplay']) {
                $queryString['autoplay'] = (int) $options['autoplay'];
                $queryString['playsinline'] = (int) $options['autoplay'];
            }

            return 'https://player.twitch.tv/?'.http_build_query($queryString);
        }

        return $this->embedId;
    }
}
