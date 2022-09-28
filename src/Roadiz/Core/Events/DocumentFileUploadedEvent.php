<?php

declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

/**
 * @deprecated This event is dispatch before Document is flushed into DB thus document Identifier is not known to Messenger dispatchers. Use DocumentFileUpdatedEvent instead.
 */
final class DocumentFileUploadedEvent extends FilterDocumentEvent
{
}
