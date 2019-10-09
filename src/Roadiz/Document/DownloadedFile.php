<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document;

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
}
