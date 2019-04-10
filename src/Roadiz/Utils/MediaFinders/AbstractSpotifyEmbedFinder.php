<?php
/**
 * roadiz - AbstractMixcloudEmbedFinder.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-04-10
 */
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use Doctrine\Common\Persistence\ObjectManager;
use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;
use RZ\Roadiz\Core\Models\DocumentInterface;

abstract class AbstractSpotifyEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'spotify';
    protected static $idPattern = '#^https\:\/\/open\.spotify\.com\/(?<type>track|playlist|artist|album|show)\/(?<id>[a-zA-Z0-9]+)#';

    /**
     * Validate extern Id against platform naming policy.
     *
     * @param string $embedId
     * @return string
     */
    protected function validateEmbedId($embedId = "")
    {
        if (preg_match(static::$idPattern, $embedId, $matches)) {
            return $embedId;
        }
        throw new \InvalidArgumentException('embedId.is_not_valid');
    }

    /**
     * @inheritDoc
     */
    public function getMediaFeed($search = null)
    {
        $endpoint = "https://embed.spotify.com/oembed";
        $query = [
            'url' => $this->embedId,
            'format' => 'json',
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
        return $this->getFeed()['title'];
    }

    /**
     * @inheritDoc
     */
    public function getMediaDescription()
    {
        return $this->getFeed()['description'];
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
        return $this->getFeed()['thumbnail_url'];
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailName($pathinfo)
    {
        if (preg_match('#\.(?<extension>[jpe?g|png|gif])$#', $pathinfo, $ext)) {
            $pathinfo = '.' . $matches['extension'];
        } else {
            $pathinfo = '.jpg';
        }
        if (preg_match(static::$idPattern, $this->embedId, $matches)) {
            return $matches['type'] . '_' . $matches['id'] . $pathinfo;
        }
        throw new \InvalidArgumentException('embedId.is_not_valid');
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
            return 'https://open.spotify.com/embed/' . $matches['type'] . '/' . $matches['id'];
        }

        return $this->embedId;
    }
}
