<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RZ\Roadiz\Documents\Models\DocumentInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Easily create and serve ZIP archives from your Roadiz documents.
 */
final class DocumentArchiver
{
    private FilesystemOperator $documentsStorage;

    public function __construct(FilesystemOperator $documentsStorage)
    {
        $this->documentsStorage = $documentsStorage;
    }

    /**
     * @param array $documents
     * @param string $name
     * @param bool $keepFolders
     * @return string Zip file path
     * @throws FilesystemException
     */
    public function archive(array $documents, string $name, bool $keepFolders = true): string
    {
        $fs = new Filesystem();
        $filename = (new AsciiSlugger())->slug($name, '_') . '.zip';

        $tmpFileName = tempnam(sys_get_temp_dir(), $filename);
        if (false === $tmpFileName) {
            throw new \RuntimeException('Can\'t create temporary file');
        }

        $zip = new \ZipArchive();
        $zip->open($tmpFileName, \ZipArchive::CREATE);

        /** @var DocumentInterface $document */
        foreach ($documents as $document) {
            if (null !== $rawDocument = $document->getRawDocument()) {
                $document = $rawDocument;
            }
            if ($document->isLocal()) {
                if ($this->documentsStorage->fileExists($document->getMountPath())) {
                    if ($keepFolders) {
                        $zipPathname = $document->getFolder() . DIRECTORY_SEPARATOR . $document->getFilename();
                    } else {
                        $zipPathname = $document->getFilename();
                    }
                    $zip->addFromString($zipPathname, $this->documentsStorage->read($document->getMountPath()));
                }
            }
        }
        $zip->close();

        return $tmpFileName;
    }

    public function archiveAndServe(array $documents, string $name, bool $keepFolders = true, bool $unlink = true): Response
    {
        $filename = $this->archive($documents, $name, $keepFolders);
        $basename = (new AsciiSlugger())->slug($name, '_')->lower() . '.zip';
        $response = new Response(
            file_get_contents($filename),
            Response::HTTP_OK,
            [
                'cache-control' => 'private',
                'content-type' => 'application/zip',
                'content-length' => filesize($filename),
                'content-disposition' => 'attachment; filename=' . $basename,
            ]
        );

        if ($unlink) {
            unlink($filename);
        }

        return $response;
    }
}
