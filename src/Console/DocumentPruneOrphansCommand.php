<?php

declare(strict_types=1);

namespace RZ\Roadiz\Documents\Console;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Documents\Models\DocumentInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final class DocumentPruneOrphansCommand extends AbstractDocumentCommand
{
    protected SymfonyStyle $io;

    protected function configure()
    {
        $this->setName('documents:prune:orphans')
            ->setDescription('Remove any document without existing file on filesystem, except embeds. <info>Danger zone</info>')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE)
        ;
    }

    /**
     * @return QueryBuilder
     */
    protected function getDocumentQueryBuilder(): QueryBuilder
    {
        return $this->getDocumentRepository()->createQueryBuilder('d');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $em = $this->getManager();
        $filesystem = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        if ($dryRun) {
            $this->io->note('Dry run');
        }
        $deleteCount = 0;

        $this->onEachDocument(function (DocumentInterface $document) use ($filesystem, $em, $deleteCount, $dryRun) {
            $this->checkDocumentFilesystem($document, $filesystem, $em, $deleteCount, $dryRun);
        }, new SymfonyStyle($input, $output));

        $this->io->success(sprintf('%d documents were deleted.', $deleteCount));
        return 0;
    }

    /**
     * @param DocumentInterface $document
     * @param Filesystem $filesystem
     * @param ObjectManager $entityManager
     * @param int $deleteCount
     * @param bool $dryRun
     */
    private function checkDocumentFilesystem(
        DocumentInterface $document,
        Filesystem $filesystem,
        ObjectManager $entityManager,
        int &$deleteCount,
        bool $dryRun = false
    ): void {
        /*
         * Do not prune embed documents which may not have any file
         */
        if (!$document->isEmbed()) {
            $documentPath = $this->packages->getDocumentFilePath($document);
            if (!$filesystem->exists($documentPath)) {
                if ($this->io->isDebug() && !$this->io->isQuiet()) {
                    $this->io->writeln(sprintf(
                        '%s file does not exist, pruning document %s',
                        $document->getRelativePath(),
                        (string) $document
                    ));
                }
                if (!$dryRun) {
                    $entityManager->remove($document);
                    $deleteCount++;
                }
            }
        }
    }
}
