<?php

declare(strict_types=1);

namespace RZ\Roadiz\Document;

use RZ\Roadiz\Core\Models\DocumentInterface;

interface DocumentFinderInterface
{
    /**
     * @param array<string> $fileNames
     *
     * @return iterable<DocumentInterface>
     */
    public function findAllByFilenames(array $fileNames): iterable;

    /**
     * @param string $fileName
     *
     * @return iterable<DocumentInterface>
     */
    public function findVideosWithFilename(string $fileName): iterable;

    /**
     * @param string $fileName
     *
     * @return iterable<DocumentInterface>
     */
    public function findAudiosWithFilename(string $fileName): iterable;

    /**
     * @param string $fileName
     *
     * @return iterable<DocumentInterface>
     */
    public function findPicturesWithFilename(string $fileName): iterable;

    /**
     * @param array<string> $fileNames
     *
     * @return DocumentInterface|null
     */
    public function findOneByFilenames(array $fileNames): ?DocumentInterface;
}
