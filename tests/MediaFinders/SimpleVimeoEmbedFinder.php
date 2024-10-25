<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\Tests\MediaFinders;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Documents\MediaFinders\AbstractVimeoEmbedFinder;
use RZ\Roadiz\Documents\Models\DocumentInterface;

final class SimpleVimeoEmbedFinder extends AbstractVimeoEmbedFinder
{
    /**
     * @inheritDoc
     */
    protected function documentExists(ObjectManager $objectManager, string $embedId, ?string $embedPlatform): bool
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * @inheritDoc
     */
    protected function injectMetaInDocument(ObjectManager $objectManager, DocumentInterface $document): DocumentInterface
    {
        throw new \RuntimeException('Not implemented');
    }
}
