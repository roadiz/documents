<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

/**
 * Event dispatched on document updated AFTER DB flushed
 */
final class DocumentUpdatedEvent extends FilterDocumentEvent
{
}
