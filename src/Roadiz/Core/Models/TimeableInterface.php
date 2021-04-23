<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

interface TimeableInterface
{
    /**
     * @return int
     */
    public function getMediaDuration(): int;

    /**
     * @param int $duration
     * @return static
     */
    public function setMediaDuration(int $duration);
}
