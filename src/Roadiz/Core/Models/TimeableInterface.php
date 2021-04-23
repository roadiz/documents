<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

interface TimeableInterface
{
    public function getMediaDuration(): int;
    public function setMediaDuration(int $duration);
}
