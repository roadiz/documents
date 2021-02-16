<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Models\DocumentInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @package RZ\Roadiz\Core\Events
 */
class FilterDocumentEvent extends Event
{
    protected DocumentInterface $document;

    public function __construct(DocumentInterface $document)
    {
        $this->document = $document;
    }

    /**
     * @return DocumentInterface
     */
    public function getDocument(): DocumentInterface
    {
        return $this->document;
    }
}
