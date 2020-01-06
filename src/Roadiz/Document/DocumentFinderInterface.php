<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document;

use RZ\Roadiz\Core\Models\DocumentInterface;
use Traversable;

interface DocumentFinderInterface
{
    /**
     * @param array<string> $fileNames
     *
     * @return array<DocumentInterface>|Traversable<DocumentInterface>
     */
    public function findAllByFilenames(array $fileNames);

    /**
     * @param array<string> $fileNames
     *
     * @return DocumentInterface|null
     */
    public function findOneByFilenames(array $fileNames): ?DocumentInterface;
}
