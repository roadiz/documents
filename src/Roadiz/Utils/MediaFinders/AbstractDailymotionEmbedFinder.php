<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

/**
 * Dailymotion tools class.
 *
 * Manage a youtube video feed
 */
abstract class AbstractDailymotionEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'dailymotion';

    /**
     * {@inheritdoc}
     */
    public function getMediaTitle()
    {
        return $this->getFeed()['title'];
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
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
        return $this->getFeed()['thumbnail_url'];
    }


    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
        // http://gdata.youtube.com/feeds/api/videos/<Code de la vidéo>?v=2&alt=json ---> JSON
        //
        $url = "http://www.dailymotion.com/services/oembed?format=json&url=".
                "http://www.dailymotion.com/video/".
                $this->embedId;

        return $this->downloadFeedFromAPI($url);
    }

    /**
     * Get embed media source URL.
     *
     * ## Available fields
     *
     * * loop
     * * autoplay
     * * controls
     *
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = []): string
    {
        parent::getSource($options);

        $queryString = [];

        $queryString['autoplay'] = (int) $options['autoplay'];
        $queryString['loop'] = (int) $options['loop'];
        $queryString['controls'] = (int) $options['controls'];
        $queryString['muted'] = (int) $options['muted'];

        return 'https://www.dailymotion.com/embed/video/'.$this->embedId . '?' . http_build_query($queryString);
    }
}
