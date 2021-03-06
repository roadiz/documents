<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

/**
 * Simple FileAwareInterface implementation for tests purposes.
 *
 * @package RZ\Roadiz\Core\Models
 */
class SimpleFileAware implements FileAwareInterface
{
    private string $basePath;

    /**
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function getPublicFilesPath(): string
    {
        return $this->basePath . $this->getPublicFilesBasePath();
    }

    public function getPublicFilesBasePath(): string
    {
        return '/files';
    }

    public function getPrivateFilesPath(): string
    {
        return $this->basePath . $this->getPrivateFilesBasePath();
    }

    public function getPrivateFilesBasePath(): string
    {
        return '/private';
    }

    public function getFontsFilesPath(): string
    {
        return $this->basePath . $this->getPrivateFilesBasePath();
    }

    public function getFontsFilesBasePath(): string
    {
        return '/fonts';
    }
}
