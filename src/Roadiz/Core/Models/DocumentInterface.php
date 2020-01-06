<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
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
 * @file DocumentInterface.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Core\Models;

use Doctrine\Common\Collections\Collection;

interface DocumentInterface
{
    /**
     * @return string
     */
    public function getFilename();

    /**
     * @param string $filename
     * @return DocumentInterface
     */
    public function setFilename($filename);

    /**
     * @return string
     */
    public function getMimeType();

    /**
     * @param string $mimeType
     * @return DocumentInterface
     */
    public function setMimeType($mimeType);

    /**
     * Get short type name for current document Mime type.
     *
     * @return string
     */
    public function getShortType();

    /**
     * Get short Mime type.
     *
     * @return string
     */
    public function getShortMimeType();

    /**
     * Is current document an image.
     *
     * @return boolean
     */
    public function isImage();

    /**
     * Is current document a vector SVG file.
     *
     * @return boolean
     */
    public function isSvg();

    /**
     * Is current document a Webp image.
     *
     * @return boolean
     */
    public function isWebp();

    /**
     * Is current document a video.
     *
     * @return boolean
     */
    public function isVideo();

    /**
     * Is current document an audio file.
     *
     * @return boolean
     */
    public function isAudio();

    /**
     * Is current document a PDF file.
     *
     * @return bool
     */
    public function isPdf();

    /**
     * @return string
     */
    public function getFolder();

    /**
     * Set folder name.
     *
     * @param string $folder
     * @return DocumentInterface
     */
    public function setFolder($folder);

    /**
     * @deprecated Use getRelativePath instead, naming is better.
     * @return string|null
     */
    public function getRelativeUrl();

    /**
     * @return string|null
     */
    public function getRelativePath();

    /**
     * @return string
     */
    public function getEmbedId();

    /**
     * @param string $embedId
     * @return DocumentInterface
     */
    public function setEmbedId($embedId);

    /**
     * @return string
     */
    public function getEmbedPlatform();

    /**
     * @param string $embedPlatform
     * @return DocumentInterface
     */
    public function setEmbedPlatform($embedPlatform);

    /**
     * Tells if current document has embed media information.
     *
     * @return boolean
     */
    public function isEmbed();

    /**
     * @return boolean
     */
    public function isPrivate();

    /**
     * @param boolean $private
     * @return DocumentInterface
     */
    public function setPrivate($private);

    /**
     * Gets the value of rawDocument.
     *
     * @return DocumentInterface|null
     */
    public function getRawDocument();

    /**
     * Sets the value of rawDocument.
     *
     * @param DocumentInterface|null $rawDocument the raw document
     * @return DocumentInterface
     */
    public function setRawDocument(DocumentInterface $rawDocument = null);

    /**
     * Is document a raw one.
     *
     * @return boolean
     */
    public function isRaw();

    /**
     * Sets the value of raw.
     *
     * @param boolean $raw the raw
     * @return DocumentInterface
     */
    public function setRaw($raw);

    /**
     * Gets the downscaledDocument.
     *
     * @return DocumentInterface|null
     */
    public function getDownscaledDocument();

    /**
     * @return Collection<FolderInterface>
     */
    public function getFolders();

    /**
     * @param FolderInterface $folder
     * @return DocumentInterface
     */
    public function addFolder(FolderInterface $folder);

    /**
     * @param FolderInterface $folder
     * @return DocumentInterface
     */
    public function removeFolder(FolderInterface $folder);

    /**
     * Return true if current document can be processed by intervention-image (GD, Imagick…).
     *
     * @return boolean
     */
    public function isProcessable();

    /**
     * @return string
     */
    public function getAlternativeText(): string;
}
