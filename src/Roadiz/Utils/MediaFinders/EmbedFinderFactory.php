<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

/**
 * @package RZ\Roadiz\Utils\MediaFinders
 */
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
     * @var array
     *
     */
    private array $embedPlatforms;

    /**
     * @param array $embedPlatforms
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
            )
        ;
    }
}
