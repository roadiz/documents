<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document;

use GuzzleHttp\Exception\RequestException;
use RZ\Roadiz\Utils\StringHandler;
use Symfony\Component\HttpFoundation\File\File;

class DownloadedFile extends File
{
    protected ?string $originalFilename;

    /**
     * @return string|null
     */
    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    /**
     * @param string|null $originalFilename
     *
     * @return DownloadedFile
     */
    public function setOriginalFilename(?string $originalFilename)
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    /**
     * Final constructor for safe usage in DownloadedFile::fromUrl
     *
     * @param string $path
     * @param bool   $checkPath
     */
    final public function __construct(string $path, bool $checkPath = true)
    {
        parent::__construct($path, $checkPath);
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
            /*
             * Some OEmbed providers won't add any extension in original filename.
             */
            if ($file->getExtension() === '' && null !== $guessedExtension = $file->guessExtension()) {
                $file->setOriginalFilename($file->getOriginalFilename().'.'.$guessedExtension);
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
