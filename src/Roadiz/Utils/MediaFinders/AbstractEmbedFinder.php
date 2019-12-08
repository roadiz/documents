<?php
declare(strict_types=1);
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
 * @file AbstractEmbedFinder.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Utils\MediaFinders;

use Doctrine\Common\Persistence\ObjectManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface;
use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Document\DownloadedFile;
use RZ\Roadiz\Utils\Document\AbstractDocumentFactory;
use RZ\Roadiz\Utils\Document\ViewOptionsResolver;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract class to handle external media via their Json API.
 *
 * @package RZ\Roadiz\Utils\MediaFinders
 */
abstract class AbstractEmbedFinder implements EmbedFinderInterface
{
    /**
     * @var array|null
     */
    protected $feed = null;
    /**
     * @var string
     */
    protected $embedId;
    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected static $platform = 'abstract';

    /**
     * AbstractEmbedFinder constructor.
     * @param string $embedId
     * @param bool $validate Validate the embed id passed at the constructor [default: true].
     */
    public function __construct($embedId = '', $validate = true)
    {
        if ($validate) {
            $this->embedId = $this->validateEmbedId($embedId);
        } else {
            $this->embedId = $embedId;
        }
    }

    /**
     * @return string
     */
    public function getEmbedId()
    {
        return $this->embedId;
    }

    /**
     * @param string $embedId
     */
    public function setEmbedId($embedId)
    {
        $this->embedId = $this->validateEmbedId($embedId);
    }

    /**
     * Validate extern Id against platform naming policy.
     *
     * @param string $embedId
     * @return string
     */
    protected function validateEmbedId($embedId = "")
    {
        if (preg_match('#(?<id>[^\/^=^?]+)$#', $embedId, $matches)) {
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
        if ($this->getFeed() !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Crawl and parse an API json feed for current embedID.
     *
     * @return array|bool
     */
    public function getFeed()
    {
        if (null === $this->feed) {
            $rawFeed = $this->getMediaFeed();
            if ($rawFeed instanceof StreamInterface) {
                $rawFeed = $rawFeed->getContents();
            }
            if (false !== $rawFeed) {
                $this->feed = json_decode($rawFeed, true);
            }
        }
        return $this->feed;
    }

    /**
     * Get embed media source URL.
     *
     * @param array $options
     *
     * @return string
     */
    public function getSource(array &$options = []): string
    {
        $resolver = new ViewOptionsResolver();
        $options = $resolver->resolve($options);

        return "";
    }

    /**
     * Crawl an embed API to get a Json feed.
     *
     * @param string|bool $search
     *
     * @return string|\Psr\Http\Message\StreamInterface
     */
    abstract public function getMediaFeed($search = null);

    /**
     * Crawl an embed API to get a Json feed against a search query.
     *
     * @param string  $searchTerm
     * @param string  $author
     * @param integer $maxResults
     *
     * @return string|null
     */
    abstract public function getSearchFeed($searchTerm, $author, $maxResults = 15);

    /**
     * Compose an HTML iframe for viewing embed media.
     *
     * * width
     * * height
     * * title
     * * id
     * * class
     *
     * @param array $options
     * @final
     * @return string
     */
    final public function getIFrame(array &$options = []): string
    {
        $attributes = [];
        /*
         * getSource method will resolve all options for us.
         */
        $attributes['src'] = $this->getSource($options);
        $attributes['allow'] = [
            'accelerometer',
            'encrypted-media',
            'gyroscope',
            'picture-in-picture'
        ];

        if ($options['width'] > 0) {
            $attributes['width'] = $options['width'];

            /*
             * Default height is defined to 16:10
             */
            if ($options['height'] === 0) {
                $attributes['height'] = (int)(($options['width']*10)/16);
            }
        }

        if ($options['height'] > 0) {
            $attributes['height'] = $options['height'];
        }

        $attributes['title'] = $options['title'];
        $attributes['id'] = $options['id'];
        $attributes['class'] = $options['class'];

        if ($options['autoplay']) {
            $attributes['allow'][] = 'autoplay';
        }

        if ($options['fullscreen']) {
            $attributes['allowFullScreen'] = true;
            $attributes['allow'][] = 'fullscreen';
        }

        if (count($attributes['allow']) > 0) {
            $attributes['allow'] = implode('; ', $attributes['allow']);
        }

        if ($options['loading']) {
            $attributes['loading'] = $options['loading'];
        }

        $attributes = array_filter($attributes);

        $htmlAttrs = [];
        foreach ($attributes as $key => $value) {
            if ($value == '' || $value === true) {
                $htmlAttrs[] = $key;
            } else {
                $htmlAttrs[] = $key.'="'.addslashes((string) $value).'"';
            }
        }

        return '<iframe '.implode(' ', $htmlAttrs).'></iframe>';
    }

    /**
     * Create a Document from an embed media.
     *
     * Be careful, this method does not flush.
     *
     * @param ObjectManager $objectManager
     * @param AbstractDocumentFactory $documentFactory
     * @return DocumentInterface
     */
    public function createDocumentFromFeed(
        ObjectManager $objectManager,
        AbstractDocumentFactory $documentFactory
    ) {
        if ($this->documentExists($objectManager, $this->embedId, static::$platform)) {
            throw new \InvalidArgumentException('embed.document.already_exists');
        }

        try {
            /** @var File $file */
            $file = $this->downloadThumbnail();

            if (!$this->exists() || null === $file) {
                throw new \RuntimeException('no.embed.document.found');
            }

            $documentFactory->setFile($file);
            $document = $documentFactory->getDocument();
            /*
             * Create document metas
             * for each translation
             */
            $this->injectMetaInDocument($objectManager, $document);
        } catch (APINeedsAuthentificationException $exception) {
            $document = $documentFactory->getDocument(true);
            $document->setFilename(static::$platform . '_' . $this->embedId . '.jpg');
        } catch (RequestException $exception) {
            $document = $documentFactory->getDocument(true);
            $document->setFilename(static::$platform . '_' . $this->embedId . '.jpg');
        }

        if (null === $document) {
            throw new \RuntimeException('document.cannot_persist');
        }

        $document->setEmbedId($this->getEmbedId());
        $document->setEmbedPlatform(static::$platform);

        return $document;
    }

    /**
     * @param ObjectManager $objectManager
     * @param string $embedId
     * @param string $embedPlatform
     * @return bool
     */
    abstract protected function documentExists(ObjectManager $objectManager, $embedId, $embedPlatform);

    /**
     * Store additional information into Document.
     *
     * @param ObjectManager $objectManager
     * @param DocumentInterface $document
     * @return DocumentInterface
     */
    abstract protected function injectMetaInDocument(ObjectManager $objectManager, DocumentInterface $document);

    /**
     * Get media title from feed.
     *
     * @return string
     */
    abstract public function getMediaTitle();

    /**
     * Get media description from feed.
     *
     * @return string
     */
    abstract public function getMediaDescription();

    /**
     * Get media copyright from feed.
     *
     * @return string
     */
    abstract public function getMediaCopyright();

    /**
     * Get media thumbnail external URL from its feed.
     *
     * @return string|bool
     */
    abstract public function getThumbnailURL();

    /**
     * Send a CURL request and get its string output.
     *
     * @param string $url
     * @return \Psr\Http\Message\StreamInterface
     * @throws \RuntimeException
     */
    public function downloadFeedFromAPI($url)
    {
        $client = new Client();
        $response = $client->get($url);

        if (Response::HTTP_OK == $response->getStatusCode()) {
            return $response->getBody();
        }

        throw new \RuntimeException($response->getReasonPhrase());
    }

    /**
     * @param $pathinfo
     *
     * @return string
     */
    public function getThumbnailName($pathinfo)
    {
        return $this->embedId.'_'.$pathinfo;
    }

    /**
     * Download a picture from the embed media platform
     * to get a thumbnail.
     *
     * @return File|null
     */
    public function downloadThumbnail()
    {
        $url = $this->getThumbnailURL();

        if (false !== $url && '' !== $url) {
            $thumbnailName = $this->getThumbnailName(basename($url));
            return DownloadedFile::fromUrl($url, $thumbnailName);
        }

        return null;
    }

    /**
     * Gets the value of key.
     *
     * Key is the access_token which could be asked to consume an API.
     * For example, for Youtube it must be your API server key. For SoundCloud
     * it should be you app client Id.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Sets the value of key.
     *
     * Key is the access_token which could be asked to consume an API.
     * For example, for Youtube it must be your API server key. For Soundcloud
     * it should be you app client Id.
     *
     * @param string $key the key
     *
     * @return self
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }
}
