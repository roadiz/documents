<?php
declare(strict_types=1);

namespace RZ\Roadiz\Core\Events;

/**
 *
 */
final class DocumentEvents
{
    /**
     * Event document.created is triggered each time a document
     * is created.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_CREATED = DocumentCreatedEvent::class;

    /**
     * Event document.updated is triggered each time a document
     * is updated.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_UPDATED = DocumentUpdatedEvent::class;

    /**
     * Event document_translation.updated is triggered each time a document
     * translation is updated.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_TRANSLATION_UPDATED = DocumentTranslationUpdatedEvent::class;

    /**
     * Event document.deleted is triggered each time a document
     * is deleted.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_DELETED = DocumentDeletedEvent::class;

    /**
     * Event document.image.uploaded is triggered each time a document
     * valid image formatted is uploaded. Even if the document is new or existing.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_IMAGE_UPLOADED = DocumentImageUploadedEvent::class;

    /**
     * Event document.file.uploaded is triggered each time a document
     * valid file is uploaded. Even if the document is new or existing.
     * Be careful, this event can be triggered along side document.image.uploaded
     * or document.svg.uploaded events.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_FILE_UPLOADED = DocumentFileUploadedEvent::class;

    /**
     * Event document.svg.uploaded is triggered each time a document
     * valid SVG formatted is uploaded. Even if the document is new or existing.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_SVG_UPLOADED = DocumentSvgUploadedEvent::class;

    /**
     * Event document.in.folder is triggered each time a document is linked to a folder.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_IN_FOLDER = DocumentInFolderEvent::class;

    /**
     * Event document.out.folder is triggered each time a document is linked to a folder.
     *
     * Event listener will be given a:
     * RZ\Roadiz\Core\Events\FilterDocumentEvent instance
     *
     * @var string
     */
    const DOCUMENT_OUT_FOLDER = DocumentOutFolderEvent::class;
}
