<?php
declare(strict_types=1);

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
