<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Util to grab a facebook profile picture from userAlias.
 */
abstract class AbstractSplashbasePictureFinder extends AbstractEmbedFinder
{
    /**
     * @var Client
     */
    private $client;

    protected static $platform = 'splashbase';

    /**
     * SplashbasePictureFinder constructor.
     * @param string $embedId
     */
    public function __construct($embedId = '')
    {
        parent::__construct($embedId);

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://www.splashbase.co',
            // You can set any number of default request options.
            'timeout'  => 5.0,
        ]);
    }

    protected function validateEmbedId($embedId = "")
    {
        return $embedId;
    }

    /**
     * @see http://www.splashbase.co/api#images_random
     * @return array|null
     */
    public function getRandom()
    {
        try {
            $response = $this->client->get('/api/v1/images/random', [
                'query' => [
                    'images_only' => 'true'
                ]
            ]);
            $feed = json_decode($response->getBody()->getContents(), true) ?? null;
            if (!is_array($feed)) {
                return null;
            }
            $url = $this->getBestUrl($feed);

            if (is_string($url)) {
                if (false !== strpos($url, '.jpg') || false !== strpos($url, '.png')) {
                    $this->embedId = $feed['id'];
                    $this->feed = $feed;
                    return $this->feed;
                }
            }
            return null;
        } catch (ClientException $e) {
            return null;
        }
    }

    /**
     * @param string $keyword
     *
     * @return array|bool|mixed
     */
    public function getRandomBySearch($keyword)
    {
        try {
            $query = [
                'query' => $keyword,
            ];
            $response = $this->client->get('/api/v1/images/search', [
                'query' => $query
            ]);
            $multipleFeed = json_decode($response->getBody()->getContents(), true);
            if (is_array($multipleFeed) && isset($multipleFeed['images']) && count($multipleFeed['images']) > 0) {
                $maxIndex = count($multipleFeed['images']) - 1;
                $feed = $multipleFeed['images'][rand(0, $maxIndex)];
                $url = $this->getBestUrl($feed);

                if (is_string($url)) {
                    if (false !== strpos($url, '.jpg') || false !== strpos($url, '.png')) {
                        $this->embedId = $feed['id'];
                        $this->feed = $feed;
                        return $this->feed;
                    }
                }
            }
            return false;
        } catch (ClientException $e) {
            return false;
        }
    }


    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFeed($searchTerm, $author, $maxResults = 15)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaTitle()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaDescription()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaCopyright()
    {
        return $this->feed['copyright'].' — '.$this->feed['site'];
    }

    /**
     * @inheritdoc
     */
    public function getThumbnailURL()
    {
        if (null === $this->feed) {
            $feed = $this->getRandom();

            if (null === $feed) {
                return null;
            }
        }
        /*
         * http://www.splashbase.co/api#images_random
         */
        if (is_array($this->feed)) {
            return $this->getBestUrl($this->feed);
        }
        return null;
    }

    /**
     * @param array|null $feed
     *
     * @return string|null
     */
    protected function getBestUrl(?array $feed)
    {
        if (null === $feed) {
            return null;
        }
        if (!empty($feed['large_url']) &&
            (false !== strpos($feed['large_url'], '.jpg') || false !== strpos($feed['large_url'], '.png'))) {
            return $feed['large_url'];
        }
        return isset($feed['url']) ? $feed['url'] : null;
    }
}
