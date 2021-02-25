<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;

/**
 * Manage a dailymotion video feed.
 */
abstract class AbstractDailymotionEmbedFinder extends AbstractEmbedFinder
{
    protected static string $platform = 'dailymotion';
    protected static string $idPattern = '#^https\:\/\/(?:www\.)?(?:dailymotion\.com|dai\.ly)\/video\/(?<id>[a-zA-Z0-9\_\-]+)#';
    protected static string $realIdPattern = '#^(?<id>[a-zA-Z0-9\_\-]+)$#';
    protected ?string $embedUrl;

    /**
     * @inheritDoc
     */
    protected function validateEmbedId(string $embedId = ""): string
    {
        if (preg_match(static::$idPattern, $embedId, $matches) === 1) {
            return $embedId;
        }
        if (preg_match(static::$realIdPattern, $embedId, $matches) === 1) {
            return $embedId;
        }
        throw new InvalidEmbedId($embedId, static::$platform);
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaTitle(): string
    {
        return $this->getFeed()['title'] ?? '';
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaDescription(): string
    {
        return "";
    }
    /**
     * {@inheritdoc}
     */
    public function getMediaCopyright(): string
    {
        return "";
    }
    /**
     * {@inheritdoc}
     */
    public function getThumbnailURL(): string
    {
        return $this->getFeed()['thumbnail_url'] ?? '';
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
        if (is_array($feed) &&
            !empty($feed['html']) &&
            preg_match('#src\=\"https\:\/\/www\.dailymotion\.com\/embed\/video\/(?<realId>[a-zA-Z0-9\_\-]+)#', $feed['html'], $matches)) {
            $this->embedId = urldecode($matches['realId']);
        }

        return $feed;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaFeed($search = null)
    {
        if (preg_match(static::$realIdPattern, $this->embedId, $matches)) {
            $url = 'https://www.dailymotion.com/video/' . $this->embedId;
        } else {
            $url = $this->embedId;
        }

        $endpoint = "https://www.dailymotion.com/services/oembed";
        $query = [
            'url' => $url,
            'format' => 'json',
        ];

        return $this->downloadFeedFromAPI($endpoint . '?' . http_build_query($query));
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailName(string $pathinfo): string
    {
        if (null === $this->embedUrl) {
            $embed = $this->embedId;
        } else {
            $embed = $this->embedUrl;
        }
        if (preg_match('#\.(?<extension>[jpe?g|png|gif])$#', $pathinfo, $matches) === 1) {
            $pathinfo = '.' . $matches['extension'];
        } else {
            $pathinfo = '.jpg';
        }
        if (preg_match(static::$realIdPattern, $embed, $matches) === 1) {
            return 'dailymotion_' . $matches['id'] . $pathinfo;
        }
        if (preg_match(static::$idPattern, $embed, $matches) === 1) {
            return 'dailymotion_' . $matches['id'] . $pathinfo;
        }
        throw new InvalidEmbedId($embed, static::$platform);
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
