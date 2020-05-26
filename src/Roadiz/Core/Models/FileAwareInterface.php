<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Models;

/**
 * Provide paths for file management.
 *
 * @package RZ\Roadiz\Core
 */
interface FileAwareInterface
{
    /**
     * @return string Return absolute path to public files folder.
     */
    public function getPublicFilesPath();

    /**
     * @return string Return relative path to public files folder.
     */
    public function getPublicFilesBasePath();

    /**
     * @return string Return absolute path to private files folder. Path must be protected.
     */
    public function getPrivateFilesPath();

    /**
     * @return string Return relative path to private files folder.
     */
    public function getPrivateFilesBasePath();

    /**
     * @return string Return absolute path to private font files folder. Path must be protected.
     */
    public function getFontsFilesPath();

    /**
     * @return string Return relative path to private font files folder.
     */
    public function getFontsFilesBasePath();
}
