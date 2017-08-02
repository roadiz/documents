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
 * @file FolderInterface.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */
namespace RZ\Roadiz\Core\Models;

use Doctrine\Common\Collections\Collection;
use RZ\Roadiz\Core\AbstractEntities\LeafInterface;

interface FolderInterface extends LeafInterface
{
    /**
     * @return Collection<DocumentInterface>
     */
    public function getDocuments();

    /**
     * @param DocumentInterface $document
     * @return FolderInterface
     */
    public function addDocument(DocumentInterface $document);

    /**
     * @param DocumentInterface $document
     * @return FolderInterface
     */
    public function removeDocument(DocumentInterface $document);

    /**
     * @return boolean
     */
    public function getVisible();

    /**
     * @param boolean $visible
     * @return FolderInterface
     */
    public function setVisible($visible);

    /**
     * @return string
     */
    public function getFolderName();

    /**
     * @return string
     * @deprecated Use getFolderName() method instead to differentiate from FolderTranslation’ name.
     */
    public function getName();

    /**
     * @param string $folderName
     * @return FolderInterface
     */
    public function setFolderName($folderName);

    /**
     * @param string $folderName
     * @return FolderInterface
     * @deprecated Use setFolderName() method instead to differentiate from FolderTranslation’ name.
     */
    public function setName($folderName);

    /**
     * @return string
     */
    public function getDirtyFolderName();

    /**
     * @param string $dirtyFolderName
     * @return FolderInterface
     */
    public function setDirtyFolderName($dirtyFolderName);
}