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
 * @file VimeoEmbedFinder.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Utils\MediaFinders;

/**
 * Vimeo tools class.
 *
 * Manage a Vimeo video feed
 */
abstract class AbstractVimeoEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'vimeo';

    /**
     * Validate extern Id against platform naming policy.
     *
     * @param string $embedId
     * @return string
     */
    protected function validateEmbedId($embedId = "")
    {
        if (preg_match('#(?<id>[0-9]+)$#', $embedId, $matches)) {
            return $matches['id'];
        }
        throw new \InvalidArgumentException('embedId.is_not_valid');
    }

    /**
     * Tell if embed media exists after its API feed.
     *
     * @return boolean
     */
    public function exists()
    {
        if ($this->getFeed() !== false && isset($this->getFeed()[0])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaTitle()
    {
        if (isset($this->getFeed()[0])) {
            return $this->getFeed()[0]['title'];
        }

        return "";
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        if (isset($this->getFeed()[0])) {
            return $this->getFeed()[0]['description'];
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
        if (isset($this->getFeed()[0])) {
            return $this->getFeed()[0]['thumbnail_large'];
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
