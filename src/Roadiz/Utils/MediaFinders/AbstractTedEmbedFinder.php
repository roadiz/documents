<?php
/**
 * roadiz - AbstractMixcloudEmbedFinder.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-04-10
 */
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;

abstract class AbstractTedEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'ted';
    protected static $idPattern = '#^https\:\/\/(www\.)?ted\.com\/talks\/(?<id>[a-zA-Z0-9\-\_]+)#';

    /**
     * @inheritDoc
     */
    protected function validateEmbedId($embedId = "")
    {
        if (preg_match(static::$idPattern, $embedId, $matches)) {
            return $embedId;
        }
        throw new InvalidEmbedId($embedId, static::$platform);
    }

    /**
     * @inheritDoc
     */
    public function getMediaFeed($search = null)
    {
        $endpoint = "https://www.ted.com/services/v1/oembed.json";
        $query = [
            'url' => $this->embedId,
        ];

        return $this->downloadFeedFromAPI($endpoint . '?' . http_build_query($query));
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
        return $this->getFeed()['title'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getMediaDescription()
    {
        return $this->getFeed()['description'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getMediaCopyright()
    {
        return $this->getFeed()['author_name'] . ' - ' . $this->getFeed()['provider_name'] . ' (' . $this->getFeed()['author_url']. ')';
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailURL()
    {
        return $this->getFeed()['thumbnail_url'];
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
            return 'ted_talk_' . $matches['id'] . $pathinfo;
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
    public function getSource(array &$options = [])
    {
        parent::getSource($options);

        if (preg_match(static::$idPattern, $this->embedId, $matches)) {
            return 'https://embed.ted.com/talks/' . $matches['id'];
        }

        return $this->embedId;
    }
}
