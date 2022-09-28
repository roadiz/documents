<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

/**
 * Event dispatched on document deletion BEFORE DB flushed
 */
final class DocumentDeletedEvent extends FilterDocumentEvent
{
}
