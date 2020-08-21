<?php
declare(strict_types=1);

namespace RZ\Roadiz\Document;

use RZ\Roadiz\Core\Models\DocumentInterface;

interface DocumentFinderInterface
{
    /**
     * @param array<string> $fileNames
     *
     * @return array<DocumentInterface>|\Doctrine\Common\Collections\Collection<DocumentInterface>
     */
    public function findAllByFilenames(array $fileNames);

    /**
     * @param array<string> $fileNames
     *
     * @return DocumentInterface|null
     */
    public function findOneByFilenames(array $fileNames): ?DocumentInterface;
}
