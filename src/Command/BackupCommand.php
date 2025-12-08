<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Backupper\AbstractBackupper;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\DbToolsBundle\Helper\Output\ConsoleOutput;
use MakinaCorpus\DbToolsBundle\Storage\Storage;
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
    private AbstractBackupper $backupper;
    private ?array $excludedTables = null;
    private ?string $extraOptions = null;
    private bool $ignoreDefaultOptions = false;

    public function __construct(
        private string $connectionName,
        private BackupperFactory $backupperFactory,
        private Storage $storage,
    ) {
        parent::__construct();
    }

    #[\Override]
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
            ->addOption(
                'excluded-tables',
                null,
                InputOption::VALUE_REQUIRED,
                'Tables to exclude, separate with comma (ex: users,logs). If given, overrides excluded tables parameter from bundle configuration.'
            )
            ->addOption(
                'extra-options',
                'o',
                InputOption::VALUE_REQUIRED,
                'Extra options to pass to the binary to perform the backup, added to the default ones, unless you specify --ignore-default-options.'
            )
            ->addOption(
                '--ignore-default-options',
                null,
                InputOption::VALUE_NONE,
                'Ignore default options defined in the bundle configuration.'
            )
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('connection')) {
            $this->connectionName = $input->getOption('connection');
        }
        if ($excludedTables = $input->getOption('excluded-tables')) {
            $this->excludedTables = \explode(',', $excludedTables);
        }

        $this->extraOptions = $input->getOption('extra-options');
        $this->ignoreDefaultOptions = $input->getOption('ignore-default-options');

        try {
            $created = $this->doBackup();

            if (!$input->getOption('no-cleanup')) {
                $this->cleanupBackups($created);
            }
        } catch (NotImplementedException $e) {
            $this->io->error($e->getMessage());

            return NotImplementedException::CONSOLE_EXIT_STATUS;
        }

        return Command::SUCCESS;
    }

    private function doBackup(): string
    {
        $this->io->section('Starting backup');

        $this->backupper = $this->backupperFactory->create($this->connectionName);
        $filename = $this->storage->generateFilename($this->connectionName, $this->backupper->getExtension());

        if (isset($this->excludedTables)) {
            $this->backupper->setExcludedTables($this->excludedTables);
        }

        $this
            ->backupper
            ->setDestination($filename)
            ->setExtraOptions($this->extraOptions)
            ->ignoreDefaultOptions($this->ignoreDefaultOptions)
            ->setOutput(new ConsoleOutput($this->io))
            ->setVerbose($this->io->isVerbose())
            ->execute()
        ;

        $this->io->success("Backup done: " . $filename);

        return $filename;
    }

    private function cleanupBackups(string $preserveFile): void
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
            if ($this->io->confirm("Delete ALL these files?", true)) {
                $filesystem = new Filesystem();
                $filesystem->remove(\array_map(fn ($data) => $data[1], $backupLists));
                $this->io->success(\sprintf("%s files have been deleted.", \count($backupLists)));
            }
        } else {
            $this->io->success("No old backup to remove.");
        }
    }
}
