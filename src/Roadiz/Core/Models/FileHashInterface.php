<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

interface FileHashInterface
{
    public function setFileHash(?string $hash): FileHashInterface;
    public function getFileHash(): ?string;
    public function setFileHashAlgorithm(?string $algorithm): FileHashInterface;
    public function getFileHashAlgorithm(): ?string;
}
