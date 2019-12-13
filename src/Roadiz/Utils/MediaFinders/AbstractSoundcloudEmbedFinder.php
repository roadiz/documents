<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file SoundcloudEmbedFinder.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;

/**
 * Soundcloud tools class.
 *
 * Manage a youtube video feed
 */
abstract class AbstractSoundcloudEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'soundcloud';
    protected static $idPattern = '#^https\:\/\/soundcloud\.com\/(?<user>[a-z0-9\-]+)\/?#';
    protected static $realIdPattern = '#^https\:\/\/api\.soundcloud\.com\/(?<type>tracks|playlists|users)\/(?<id>[0-9]+)\/?#';

    /**
     * @var string|null
     */
    protected $embedUrl;

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
        $endpoint = "https://soundcloud.com/oembed";
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
        $this->embedUrl = $this->embedId;
        if (!empty($feed['html']) && preg_match('#url\=(?<realId>[a-zA-Z0-9\%\.]+)\&#', $feed['html'], $matches)) {
            $this->embedId = urldecode($matches['realId']);
        }

        return $feed;
    }

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
     * @inheritDoc
     */
    public function getThumbnailName($pathinfo)
    {
        if (null === $this->embedUrl) {
            $embed = $this->embedId;
        } else {
            $embed = $this->embedUrl;
        }
        if (preg_match('#\.(?<extension>[jpe?g|png|gif])$#', $pathinfo, $ext)) {
            $pathinfo = '.' . $ext['extension'];
        } else {
            $pathinfo = '.jpg';
        }
        if (preg_match(static::$idPattern, $embed, $matches)) {
            return 'soundcloud_' . $matches['user'] . $pathinfo;
        }
        throw new InvalidEmbedId($embed, static::$platform);
    }

    /**
     * Get embed media source URL.
     *
     * ## Available fields
     *
     * * hide_related
     * * show_comments
     * * show_user
     * * show_reposts
     * * visual
     *
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = [])
    {
        parent::getSource($options);

        $queryString = [
            'url' => $this->embedId,
        ];

        $queryString['hide_related'] = (int) $options['hide_related'];
        $queryString['show_comments'] = (int) $options['show_comments'];
        $queryString['show_artwork'] = (int) $options['show_artwork'];
        $queryString['show_user'] = (int) $options['show_user'];
        $queryString['show_reposts'] = (int) $options['show_reposts'];
        $queryString['visual'] = (int) $options['visual'];
        $queryString['auto_play'] = (int) $options['autoplay'];
        $queryString['controls'] = (int) $options['controls'];

        return 'https://w.soundcloud.com/player/?' . http_build_query($queryString);
    }
}
