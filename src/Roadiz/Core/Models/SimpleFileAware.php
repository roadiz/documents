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
    /**
     * @var string
     */
    private $basePath;

    /**
     * SimpleFileAware constructor.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function getPublicFilesPath()
    {
        return $this->basePath . $this->getPublicFilesBasePath();
    }

    public function getPublicFilesBasePath()
    {
        return '/files';
    }

    public function getPrivateFilesPath()
    {
        return $this->basePath . $this->getPrivateFilesBasePath();
    }

    public function getPrivateFilesBasePath()
    {
        return '/private';
    }

    public function getFontsFilesPath()
    {
        return $this->basePath . $this->getPrivateFilesBasePath();
    }

    public function getFontsFilesBasePath()
    {
        return '/fonts';
    }
}
