<?php
/**
 * Copyright © 2015, Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file DocumentEvents.php
 * @author Ambroise Maupate
 */
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
