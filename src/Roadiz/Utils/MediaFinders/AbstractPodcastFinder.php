<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

use SimpleXMLElement;
use Doctrine\Persistence\ObjectManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Exceptions\APINeedsAuthentificationException;
use RZ\Roadiz\Core\Exceptions\InvalidEmbedId;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Document\DownloadedFile;
use RZ\Roadiz\Utils\Document\AbstractDocumentFactory;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractPodcastFinder extends AbstractEmbedFinder
{
    /**
     * @inheritDoc
     */
    protected function validateEmbedId($embedId = "")
    {
        return $embedId;
    }

    /**
     * @return array|SimpleXMLElement|null
     */
    public function getFeed()
    {
        if (null === $this->feed) {
            $rawFeed = $this->getMediaFeed();
            if ($rawFeed instanceof StreamInterface) {
                $rawFeed = $rawFeed->getContents();
            }
            if (null !== $rawFeed) {
                try {
                    $this->feed = new SimpleXMLElement($rawFeed);
                    if ($this->feed->channel->item) {
                        return $this->feed;
                    } else {
                        throw new \RuntimeException('Feed content is not a valid Podcast XML');
                    }
                } catch (\Exception $errorException) {
                    throw new \RuntimeException('Feed content is not a valid Podcast XML');
                }
            }
        }
        return $this->feed;
    }

    /**
     * @param string $pathinfo
     *
     * @return string
     */
    protected function getAudioName(SimpleXMLElement $item)
    {
        $url = (string) $item->enclosure->attributes()->url;

        if (!empty((string) $item->title)) {
            $extension = pathinfo($url, PATHINFO_EXTENSION);
            return ((string) $item->title) . '.' . $extension;
        }
        return pathinfo($url, PATHINFO_BASENAME);
    }

    /**
     * Create a Document from an embed media.
     *
     * Be careful, this method does not flush.
     *
     * @param ObjectManager $objectManager
     * @param AbstractDocumentFactory $documentFactory
     * @return DocumentInterface|array<DocumentInterface>
     */
    public function createDocumentFromFeed(
        ObjectManager $objectManager,
        AbstractDocumentFactory $documentFactory
    ) {
        $documents = [];
        foreach ($this->getFeed()->channel->item as $item) {
            if (!empty($item->enclosure->attributes()->url) &&
                !$this->documentExists($objectManager, $item->guid, null)) {
                $podcastUrl = (string) $item->enclosure->attributes()->url;
                $thumbnailName = $this->getAudioName($item);
                $file = DownloadedFile::fromUrl($podcastUrl, $thumbnailName);

                if (null !== $file) {
                    $documentFactory->setFile($file);
                    $document = $documentFactory->getDocument();
                    if (null !== $document) {
                        /*
                         * Create document metas
                         * for each translation
                         */
                        $this->injectMetaFromPodcastItem($objectManager, $document, $item);
                        $document->setEmbedId((string) $item->guid);
                        $document->setEmbedPlatform(null);
                        $documents[] = $document;
                    }
                }
            }
        }

        return $documents;
    }

    abstract protected function injectMetaFromPodcastItem(
        ObjectManager $objectManager,
        DocumentInterface $document,
        \SimpleXMLElement $item
    ): void;

    protected function getPodcastItemTitle(\SimpleXMLElement $item): string
    {
        return (string) $item->title . ' – ' . $this->getMediaTitle();
    }

    protected function getPodcastItemDescription(\SimpleXMLElement $item): string
    {
        return (string) $item->description;
    }

    protected function getPodcastItemCopyright(\SimpleXMLElement $item): string
    {
        $ituneNode = $item->children('itunes', true);
        $copyright = (string) $ituneNode->author;

        if (empty($copyright)) {
            $copyright = (string) $item->author;
        }
        if (empty($copyright)) {
            return $this->getMediaCopyright();
        }
        return $copyright . ' – ' . $this->getMediaCopyright();
    }

    /**
     * @inheritDoc
     */
    public function getMediaFeed($search = null)
    {
        $url = $this->embedId;
        $client = new Client();
        $response = $client->get($url);

        if (Response::HTTP_OK == $response->getStatusCode()) {
            return $response->getBody();
        }

        throw new \RuntimeException($response->getReasonPhrase());
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
        return (string) ($this->getFeed()->channel->title ?? null);
    }

    /**
     * @inheritDoc
     */
    public function getMediaDescription()
    {
        return (string) ($this->getFeed()->channel->description ?? null);
    }

    /**
     * @inheritDoc
     */
    public function getMediaCopyright()
    {
        return (string) ($this->getFeed()->channel->copyright ?? null);
    }

    /**
     * @inheritDoc
     */
    public function getThumbnailURL()
    {
        return (string) ($this->getFeed()->channel->image->url ?? null);
    }
}
