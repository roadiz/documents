<?php
declare(strict_types=1);

namespace RZ\Roadiz\Utils\MediaFinders;

/**
 * Class EmbedFinderFactory
 *
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
    private $embedPlatforms = [];

    /**
     * EmbedFinderFactory constructor.
     *
     * @param array $embedPlatforms
     */
    public function __construct(array $embedPlatforms)
    {
        $this->embedPlatforms = $embedPlatforms;
    }

    /**
     * @param string $mediaPlatform
     * @param string $embedId
     *
     * @return EmbedFinderInterface|null
     */
    public function createForPlatform(string $mediaPlatform, string $embedId): ?EmbedFinderInterface
    {
        if ($this->supports($mediaPlatform)) {
            $class = $this->embedPlatforms[$mediaPlatform];
            return new $class($embedId);
        }
        return null;
    }

    /**
     * @param string $mediaPlatform
     *
     * @return bool
     */
    public function supports(string $mediaPlatform): bool
    {
        return in_array(
            $mediaPlatform,
            array_keys($this->embedPlatforms)
        );
    }
}
