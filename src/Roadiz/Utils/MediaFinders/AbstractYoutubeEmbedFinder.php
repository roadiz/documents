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
 * @file YoutubeEmbedFinder.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;

/**
 * Youtube tools class.
 *
 * Manage a youtube video feed
 */
abstract class AbstractYoutubeEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'youtube';
    protected static $idPattern = '#^https\:\/\/(www\.)?youtube\.com\/watch\?v\=(?<id>[a-zA-Z0-9\_\-]+)#';
    protected static $realIdPattern = '#^(?<id>[a-zA-Z0-9\_\-]+)$#';

    /**
     * @var string|null
     */
    protected $embedUrl;

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
        if (preg_match(static::$realIdPattern, $embedId, $matches)) {
            return $embedId;
        }
        throw new \InvalidArgumentException('embedId.is_not_valid');
    }

    /**
     * @inheritDoc
     */
    public function getMediaFeed($search = null)
    {
        $endpoint = "https://www.youtube.com/oembed";
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
        if (!empty($feed['html']) && preg_match('#src\=\"https\:\/\/www\.youtube\.com\/embed\/(?<realId>[a-zA-Z0-9\_\-]+)#', $feed['html'], $matches)) {
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
            $pathinfo = '.' . $matches['extension'];
        } else {
            $pathinfo = '.jpg';
        }
        if (preg_match(static::$idPattern, $embed, $matches)) {
            return 'youtube_' . $matches['id'] . $pathinfo;
        }
        throw new \InvalidArgumentException('embedId.is_not_valid');
    }

    /**
     * @inheritdoc
     * @throws APINeedsAuthentificationException
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
        if ($this->getKey() != "") {
            $url = "https://www.googleapis.com/youtube/v3/search?q=".$searchTerm."&part=snippet&key=".$this->getKey()."&maxResults=".$maxResults;
            if (!empty($author)) {
                $url .= '&author='.$author;
            }
            return $this->downloadFeedFromAPI($url);
        } else {
            throw new APINeedsAuthentificationException("YoutubeEmbedFinder needs a Google server key, create a “google_server_id” setting.", 1);
        }
    }

    /**
     * Get embed media source URL.
     *
     * ### Youtube additional embed parameters
     *
     * * modestbrandin
     * * rel
     * * showinfo
     * * start
     * * enablejsapi
     * * muted
     *
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = [])
    {
        parent::getSource($options);

        $queryString = [
            'rel' => 0,
            'html5' => 1,
            'wmode' => 'transparent',
        ];

        if ($options['autoplay']) {
            $queryString['autoplay'] = (int) $options['autoplay'];
            $queryString['playsinline'] = (int) $options['autoplay'];
        }
        if ($options['playlist']) {
            $queryString['playlist'] = (int) $options['playlist'];
        }
        if (null !== $options['color']) {
            $queryString['color'] = $options['color'];
        }
        if ($options['start']) {
            $queryString['start'] = (int) $options['start'];
        }
        if ($options['end']) {
            $queryString['end'] = (int) $options['end'];
        }

        $queryString['loop'] = (int) $options['loop'];
        $queryString['controls'] = (int) $options['controls'];
        $queryString['fs'] = (int) $options['fullscreen'];
        $queryString['modestbranding'] = (int) $options['modestbranding'];
        $queryString['rel'] = (int) $options['rel'];
        $queryString['showinfo'] = (int) $options['showinfo'];
        $queryString['enablejsapi'] = (int) $options['enablejsapi'];
        $queryString['mute'] = (int) $options['muted'];

        return 'https://www.youtube.com/embed/'.$this->embedId.'?'.http_build_query($queryString);
    }
}
