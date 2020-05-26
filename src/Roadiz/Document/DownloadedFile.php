<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document;

use GuzzleHttp\Exception\RequestException;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\File\File;

class DownloadedFile extends File
{
    /** @var string|null */
    private $originalFilename;

    /**
     * @return mixed
     */
    public function getOriginalFilename()
    {
        return $this->originalFilename;
    }

    /**
     * @param mixed $originalFilename
     *
     * @return DownloadedFile
     */
    public function setOriginalFilename($originalFilename)
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    /**
     * @param string      $url
     * @param string|null $originalName
     *
     * @return DownloadedFile|null
     */
    public static function fromUrl(string $url, ?string $originalName = null): ?DownloadedFile
    {
        try {
            $baseName = StringHandler::cleanForFilename(pathinfo($url, PATHINFO_BASENAME));
            $distantHandle = fopen($url, 'r');
            if (false === $distantHandle) {
                return null;
            }
            $original = \GuzzleHttp\Psr7\stream_for($distantHandle);
            $tmpFile = tempnam(sys_get_temp_dir(), StringHandler::cleanForFilename($baseName));
            if (false === $tmpFile) {
                return null;
            }
            $handle = fopen($tmpFile, 'w');
            $local = \GuzzleHttp\Psr7\stream_for($handle);
            $local->write($original->getContents());
            $local->close();

            $file = new static($tmpFile);
            if (null !== $originalName && !empty($originalName)) {
                $file->setOriginalFilename($originalName);
            } else {
                $file->setOriginalFilename($baseName);
            }

            if ($file->isReadable() && filesize($file->getPathname()) > 0) {
                return $file;
            }
        } catch (RequestException $e) {
            return null;
        } catch (\ErrorException $e) {
            return null;
        }
        return null;
    }
}
