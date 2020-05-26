<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;

abstract class AbstractDeezerEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'deezer';
    protected static $idPattern = '#^https?:\/\/(www.)?deezer\.com\/(?:\\w+/)?(?<type>track|playlist|album)\/(?<id>[a-zA-Z0-9]+)#';
    protected static $realIdPattern = '#^(?<id>[a-zA-Z0-9]+)$#';

    public function isEmptyThumbnailAllowed(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function validateEmbedId($embedId = "")
    {
        if (preg_match(static::$idPattern, $embedId, $matches)) {
            return $embedId;
        }
        if (preg_match(static::$realIdPattern, $embedId, $matches)) {
            return $embedId;
        }
        throw new InvalidEmbedId($embedId, static::$platform);
    }

    /**
     * @inheritDoc
     */
    public function getMediaFeed($search = null)
    {
        $endpoint = "https://api.deezer.com/oembed";
        $query = [
            'url' => $this->embedId,
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
        if (!empty($feed['html']) &&
            preg_match(
                '#src\=[\"|\']https\:\/\/www\.deezer\.com\/plugins\/player\?type\=tracks\&id\=(?<realId>[a-zA-Z0-9\_\-]+)#',
                $feed['html'],
                $matches
            )) {
            $this->embedId = urldecode($matches['realId']);
        }

        return $feed;
    }

    /**
     * @inheritDoc
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMediaTitle()
    {
        return isset($this->getFeed()['title']) ? $this->getFeed()['title'] : '';
    }

    /**
     * @inheritDoc
     */
    public function getMediaDescription()
    {
        return isset($this->getFeed()['description']) ? $this->getFeed()['description'] : '';
    }

    /**
     * @inheritDoc
     */
    public function getMediaCopyright()
    {
        return $this->getFeed()['provider_name'] . ' (' . $this->getFeed()['provider_url']. ')';
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailURL()
    {
        return isset($this->getFeed()['thumbnail_url']) ? $this->getFeed()['thumbnail_url'] : '';
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailName($pathinfo)
    {
        if (preg_match('#\.(?<extension>[jpe?g|png|gif])$#', $pathinfo, $ext)) {
            $pathinfo = '.' . $ext['extension'];
        } else {
            $pathinfo = '.jpg';
        }
        if (preg_match(static::$idPattern, $this->embedId, $matches)) {
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
            'type' => 'tracks',
            'format' => 'classic',
            'id' => $this->embedId
        ];

        if ($options['autoplay']) {
            $queryString['autoplay'] = ((bool) $options['autoplay']) ? ('true') : ('false');
        }
        if ($options['playlist']) {
            $queryString['playlist'] = ((bool) $options['playlist']) ? ('true') : ('false');
        }
        if (null !== $options['color']) {
            $queryString['color'] = $options['color'];
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

        return 'https://www.deezer.com/plugins/player?'.http_build_query($queryString);
    }
}
