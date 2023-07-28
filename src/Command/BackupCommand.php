<?php

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactoryRegistry;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperInterface;
use MakinaCorpus\DbToolsBundle\DbToolsStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'db-tools:backup', description: 'Backup database')]
class BackupCommand extends Command
{
    private SymfonyStyle $io;
    private string $connectionName;
    private BackupperInterface $backupper;

    public function __construct(
        string $defaultConnectionName,
        private array $excludedTables,
        private BackupperFactoryRegistry $backupperFactory,
        private DbToolsStorage $storage,
    ) {
        $this->connectionName = $defaultConnectionName;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setHelp('Backup database')
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'A doctrine connection name. If not given, use default connection'
            )
            ->addOption(
                'no-cleanup',
                null,
                InputOption::VALUE_NONE,
                'Should we skip the old backups cleanup step?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        if ($input->getOption('connection')) {
            $this->connectionName = $input->getOption('connection');
        }

        $created = $this->doBackup();

        if (!$input->getOption('no-cleanup')) {
            $this->cleanupBackups($created);
        }

        return Command::SUCCESS;
    }

    private function doBackup(): string
    {
        $this->io->section('Starting backup');

        $this->backupper = $this->backupperFactory->create($this->connectionName);
        $filename = $this->storage->generateFilename($this->connectionName, $this->backupper->getExtension());

        $this->backupper
            ->setDestination($filename)
            ->setExcludedTables($this->excludedTables[$this->connectionName] ?? [])
            ->startBackup()
        ;

        foreach ($this->backupper as $data) {
            $this->io->text($data);
        }

        $this->backupper->checkSuccessful();

        $this->io->success("Backup done : " . $filename);

        $this->io->text($this->backupper->getOutput());

        return $filename;
    }

    private function cleanupBackups(string $preserveFile)
    {
        $this->io->section('Cleanups');

        $backupLists = $this->storage->listBackups(
            $this->connectionName,
            $this->backupper->getExtension(),
            true,
            $preserveFile
        );

        if (\count($backupLists)) {
            $this->io->table(
                ['Age', 'Backup filename'],
                $backupLists
            );
            if ($this->io->confirm("delete ALL these files ?", true)) {
                $filesystem = new Filesystem();
                $filesystem->remove(\array_map(fn ($data) => $data[1], $backupLists));
                $this->io->success(\sprintf("%s files have been deleted.", \count($backupLists)));
            }
        } else {
            $this->io->success("Pas d'anciens Backups à supprimer.");
        }
    }
}