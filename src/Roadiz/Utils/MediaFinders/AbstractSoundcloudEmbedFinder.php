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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soundcloud tools class.
 *
 * Manage a youtube video feed
 */
abstract class AbstractSoundcloudEmbedFinder extends AbstractEmbedFinder
{
    protected static $platform = 'soundcloud';

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
        return $this->getFeed()['artwork_url'];
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
        if ($this->getKey() != "") {
            $endpoint = "https://api.soundcloud.com/tracks/". $this->embedId;
            $query = [
                'client_id' => $this->getKey()
            ];

            return $this->downloadFeedFromAPI($endpoint . '?' . http_build_query($query));
        } else {
            throw new APINeedsAuthentificationException("Soundcloud need a clientId to perform API calls, create a “soundcloud_client_id” setting.", 1);
        }
    }

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
        /*
         * Resolve real track ID
         */
        if (preg_match('#^https?\:\/\/(www\.)?soundcloud\.com\/(.+)$#', $embedId)) {
            $endpoint = "https://api.soundcloud.com/resolve";
            $client = new Client();
            try {
                $response = $client->get($endpoint, [
                    'query' => [
                        'url' => $embedId,
                        'client_id' => $this->getKey()
                    ]
                ]);

                if (Response::HTTP_OK == $response->getStatusCode()) {
                    $trackInfo =  json_decode($response->getBody()->getContents(), true);
                    if (false !== $embedId = $this->getEmbedIdFromPlaylistFeed($trackInfo)) {
                        return $embedId;
                    } elseif (false !== $embedId = $this->getEmbedIdFromTrackFeed($trackInfo)) {
                        return $embedId;
                    }
                }
            } catch (RequestException $exception) {
                throw new \InvalidArgumentException('embedId.is_not_valid');
            }
        }

        throw new \InvalidArgumentException('embedId.is_not_valid');
    }

    /**
     * @param array $feed
     * @return bool|int
     */
    public function getEmbedIdFromPlaylistFeed(array &$feed)
    {
        if (isset($feed['tracks']) &&
            isset($feed['tracks'][0]) &&
            isset($feed['tracks'][0]['kind']) &&
            $feed['tracks'][0]['kind'] == 'track' &&
            isset($feed['tracks'][0]['id'])) {
            return $feed['tracks'][0]['id'];
        }

        return false;
    }

    /**
     * @param array $feed
     * @return bool|int
     */
    public function getEmbedIdFromTrackFeed(array &$feed)
    {
        if (isset($feed['kind']) &&
            $feed['kind'] == 'track' &&
            isset($feed['id'])) {
            return $feed['id'];
        }

        return false;
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
            'url' => 'https://api.soundcloud.com/tracks/'.$this->embedId,
        ];

        $queryString['hide_related'] = (int) $options['hide_related'];
        $queryString['show_comments'] = (int) $options['show_comments'];
        $queryString['show_user'] = (int) $options['show_user'];
        $queryString['show_reposts'] = (int) $options['show_reposts'];
        $queryString['visual'] = (int) $options['visual'];
        $queryString['auto_play'] = (int) $options['autoplay'];
        $queryString['controls'] = (int) $options['controls'];

        return 'https://w.soundcloud.com/player/?' . http_build_query($queryString);
    }
}
