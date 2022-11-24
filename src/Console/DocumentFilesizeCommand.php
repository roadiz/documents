<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\Console;

use RZ\Roadiz\Documents\Models\AdvancedDocumentInterface;
use RZ\Roadiz\Documents\Models\DocumentInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

class DocumentFilesizeCommand extends AbstractDocumentCommand
{
    protected SymfonyStyle $io;

    protected function configure()
    {
        $this->setName('documents:file:size')
            ->setDescription('Fetch every document file size (in bytes) and write it in database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->getManager();
        $this->io = new SymfonyStyle($input, $output);

        $this->onEachDocument(function (DocumentInterface $document) {
            if ($document instanceof AdvancedDocumentInterface) {
                $this->updateDocumentFilesize($document);
            }
        }, new SymfonyStyle($input, $output));
        return 0;
    }

    private function updateDocumentFilesize(AdvancedDocumentInterface $document)
    {
        if (null !== $document->getRelativePath()) {
            $documentPath = $this->packages->getDocumentFilePath($document);
            try {
                $file = new File($documentPath);
                $document->setFilesize($file->getSize());
            } catch (FileNotFoundException $exception) {
                /*
                 * Do nothing
                 * just return 0 width and height
                 */
                $this->io->error($documentPath . ' file not found.');
            }
        }
    }
}
