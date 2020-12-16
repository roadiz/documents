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
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $folderName
     * @return FolderInterface
     */
    public function setFolderName($folderName);

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
