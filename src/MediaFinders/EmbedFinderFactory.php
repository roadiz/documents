<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\MediaFinders;

class EmbedFinderFactory
{
    /**
     * Embed platform classes, for example:
     *
     * [
     *    youtube => YoutubeEmbedFinder::class,
     *    vimeo => VimeoEmbedFinder::class
     * ]
     *
     * @var array<string, class-string<EmbedFinderInterface>>
     */
    private array $embedPlatforms;

    /**
     * @param array<string, class-string<EmbedFinderInterface>> $embedPlatforms
     */
    public function __construct(array $embedPlatforms = [])
    {
        $this->embedPlatforms = $embedPlatforms;
    }

    /**
     * @param string|null $mediaPlatform
     * @param string|null $embedId
     *
     * @return EmbedFinderInterface|null
     */
    public function createForPlatform(?string $mediaPlatform, ?string $embedId): ?EmbedFinderInterface
    {
        if (null !== $embedId && $this->supports($mediaPlatform)) {
            $class = $this->embedPlatforms[$mediaPlatform];
            return new $class($embedId);
        }
        return null;
    }

    public function createForUrl(?string $embedUrl): ?EmbedFinderInterface
    {
        if (null === $embedUrl) {
            throw new \InvalidArgumentException('"embedUrl" is required');
        }
        // Throws a BadRequestHttpException if the embedUrl is not a string
        if (!is_string($embedUrl)) {
            throw new \InvalidArgumentException('"embedUrl" must be a string');
        }
        // Throws a BadRequestHttpException if the embedUrl is not a valid URL
        if (!filter_var($embedUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('"embedUrl" is not a valid URL');
        }

        // get embedFinder from embedUrl according to starting URL pattern
        switch (true) {
            case str_starts_with($embedUrl, 'https://www.youtube.com/'):
            case str_starts_with($embedUrl, 'https://youtube.com/'):
            case str_starts_with($embedUrl, 'https://youtu.be/'):
                return $this->createForPlatform('youtube', $embedUrl);
            case str_starts_with($embedUrl, 'https://vimeo.com/'):
            case str_starts_with($embedUrl, 'https://www.vimeo.com/'):
                return $this->createForPlatform('vimeo', $embedUrl);
            case str_starts_with($embedUrl, 'https://open.spotify.com'):
                return $this->createForPlatform('spotify', $embedUrl);
            case str_starts_with($embedUrl, 'https://dailymotion.com'):
            case str_starts_with($embedUrl, 'https://www.dailymotion.com'):
                return $this->createForPlatform('dailymotion', $embedUrl);
            case str_starts_with($embedUrl, 'https://twitch.tv'):
            case str_starts_with($embedUrl, 'https://www.twitch.tv'):
                return $this->createForPlatform('twitch', $embedUrl);
            case str_starts_with($embedUrl, 'https://www.ted.com/talks'):
                return $this->createForPlatform('ted', $embedUrl);
            case str_starts_with($embedUrl, 'https://www.mixcloud.com'):
                return $this->createForPlatform('mixcloud', $embedUrl);
            case str_starts_with($embedUrl, 'https://www.deezer.com'):
                return $this->createForPlatform('deezer', $embedUrl);
            case str_starts_with($embedUrl, 'https://api.soundcloud.com'):
            case str_starts_with($embedUrl, 'https://www.soundcloud.com'):
            case str_starts_with($embedUrl, 'https://soundcloud.com'):
                return $this->createForPlatform('soundcloud', $embedUrl);
        }

        return null;
    }

    /**
     * @param string|null $mediaPlatform
     *
     * @return bool
     */
    public function supports(?string $mediaPlatform): bool
    {
        return
            null !== $mediaPlatform &&
            in_array(
                $mediaPlatform,
                array_keys($this->embedPlatforms)
            );
    }
}
