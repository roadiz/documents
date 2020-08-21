<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

use RZ\Roadiz\Core\Models\DocumentInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class FilterDocumentEvent.
 *
 * @package RZ\Roadiz\Core\Events
 */
class FilterDocumentEvent extends Event
{
    /**
     * @var DocumentInterface
     */
    protected $document;

    public function __construct(DocumentInterface $document)
    {
        $this->document = $document;
    }

    /**
     * @return DocumentInterface
     */
    public function getDocument()
    {
        return $this->document;
    }
}
