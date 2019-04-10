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

abstract class AbstractMixcloudEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'mixcloud';
    protected static $idPattern = '#^https\:\/\/www\.mixcloud\.com\/(?<author>[a-zA-Z0-9\-]+)\/(?<id>[a-zA-Z0-9\-]+)\/?$#';

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
        $endpoint = "https://www.mixcloud.com/oembed/";
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
        return $this->getFeed()['author_name'] . ' (' . $this->getFeed()['author_url']. ')';
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailURL()
    {
        return $this->getFeed()['image'];
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
            return $matches['author'] . '_' . $matches['id'] . $pathinfo;
        }
        throw new \InvalidArgumentException('embedId.is_not_valid');
    }

    /**
     * Get embed media source URL.
     *
     * ### Mixcloud additional embed parameters
     *
     * * start
     * * end
     * * mini
     * * hide_cover
     *
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = [])
    {
        parent::getSource($options);

        $queryString = [
            'feed' => $this->embedId,
        ];

        if ($options['autoplay']) {
            $queryString['autoplay'] = (int) $options['autoplay'];
            $queryString['playsinline'] = (int) $options['autoplay'];
        }
        if ($options['start']) {
            $queryString['start'] = (int) $options['start'];
        }
        if ($options['end']) {
            $queryString['end'] = (int) $options['end'];
        }
        if ($options['mini'] === true) {
            $queryString['mini'] = 1;
        }
        if ($options['hide_cover'] === true) {
            $queryString['hide_cover'] = 1;
        }
        if ($options['hide_artwork'] === true) {
            $queryString['hide_artwork'] = 1;
        }
        if ($options['light'] === true) {
            $queryString['light'] = 1;
        }

        return 'https://www.mixcloud.com/widget/iframe/?'.http_build_query($queryString);
    }
}
