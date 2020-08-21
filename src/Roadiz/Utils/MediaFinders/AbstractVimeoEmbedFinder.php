<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;

/**
 * Vimeo tools class.
 *
 * Manage a Vimeo video feed
 */
abstract class AbstractVimeoEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'vimeo';

    /**
     * @inheritDoc
     */
    protected function validateEmbedId($embedId = "")
    {
        if (preg_match('#(?<id>[0-9]+)$#', $embedId, $matches)) {
            return $matches['id'];
        }
        throw new InvalidEmbedId($embedId, static::$platform);
    }

    /**
     * Tell if embed media exists after its API feed.
     *
     * @return boolean
     */
    public function exists()
    {
        $feed = $this->getFeed();
        return is_array($feed) && isset($feed[0]);
    }

    /**
     * @inheritdoc
     */
    public function getMediaTitle()
    {
        $feed = $this->getFeed();
        if (is_array($feed) && isset($feed[0])) {
            return $feed[0]['title'] ?? '';
        }

        return '';
    }
    /**
     * @inheritdoc
     */
    public function getMediaDescription()
    {
        $feed = $this->getFeed();
        if (is_array($feed) && isset($feed[0])) {
            return $feed[0]['description'] ?? '';
        }

        return "";
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaCopyright()
    {
        return "";
    }
    /**
     * {@inheritdoc}
     */
    public function getThumbnailURL()
    {
        $feed = $this->getFeed();
        if (is_array($feed) && isset($feed[0])) {
            return $feed[0]['thumbnail_large'];
        }

        return "";
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        $url = "http://gdata.youtube.com/feeds/api/videos/?q=" . $searchTerm . "&v=2&alt=json&max-results=" . $maxResults;
        if (!empty($author)) {
            $url .= '&author=' . $author;
        }

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
        // http://gdata.youtube.com/feeds/api/videos/<Code de la vidéo>?v=2&alt=json ---> JSON
        //
        $url = "http://vimeo.com/api/v2/video/" . $this->embedId . ".json";

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * Get embed media source URL.
     *
     * ### Vimeo additional embed parameters
     *
     * * displayTitle
     * * byline
     * * portrait
     * * color
     * * api
     * * muted
     * * autopause
     * * automute
     *
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = []): string
    {
        parent::getSource($options);

        $queryString = [];

        $queryString['title'] = (int) $options['displayTitle'];
        $queryString['byline'] = (int) $options['byline'];
        $queryString['portrait'] = (int) $options['portrait'];
        $queryString['api'] = (int) $options['api'];
        $queryString['loop'] = (int) $options['loop'];
        $queryString['fullscreen'] = (int) $options['fullscreen'];
        $queryString['controls'] = (int) $options['controls'];
        $queryString['autopause'] = (int) $options['autopause'];
        $queryString['automute'] = (int) $options['automute'];

        if (null !== $options['color']) {
            $queryString['color'] = $options['color'];
        }
        if (null !== $options['id']) {
            $queryString['player_id'] = $options['id'];
        }
        if (null !== $options['identifier']) {
            $queryString['player_id'] = $options['identifier'];
        }
        if ($options['autoplay']) {
            $queryString['autoplay'] = (int) $options['autoplay'];
            $queryString['playsinline'] = (int) $options['autoplay'];
        }
        if (null !== $options['background']) {
            $queryString['background'] = (int) $options['background'];
        }
        if ($options['muted']) {
            $queryString['muted'] = (int) $options['muted'];
        }

        return 'https://player.vimeo.com/video/'.$this->embedId.'?'.http_build_query($queryString);
    }
}
