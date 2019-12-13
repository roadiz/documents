<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class InvalidEmbedId extends \InvalidArgumentException
{
    /**
     * @var string
     */
    protected $embedId;
    /**
     * @var string
     */
    protected $platform;

    public function __construct(?string $embedId = null, ?string $platform = null)
    {
        parent::__construct('Embed ID is not valid for this platform', Response::HTTP_BAD_REQUEST);
        $this->embedId = $embedId;
        $this->platform = $platform;
    }

    /**
     * @return string
     */
    public function getEmbedId(): ?string
    {
        return $this->embedId;
    }

    /**
     * @return string
     */
    public function getPlatform(): ?string
    {
        return $this->platform;
    }
}
