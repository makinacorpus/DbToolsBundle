<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command\Anonymization;

use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use MakinaCorpus\DbToolsBundle\DbToolsStorage;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'db-tools:anonymization:run',
    description: 'Anonymize given backup file or the local database.',
    aliases: ['db-tools:anonymize']
)]
class AnonymizeCommand extends Command
{
    private SymfonyStyle $io;

    private string $connectionName;

    private ?string $backupFilename = null;
    private ?string $initialDatabaseBackupFilename = null;

    // Command behavior
    private bool $doAnonymizeCurrentDatabase = false;
    private ?bool $doBackupAndRestoreInitial = null;
    private bool $doCancel = false;

    // Anonmyzation options
    private ?array $excludedTargets = null;
    private ?array $onlyTargets = null;
    private bool $atOnce = true;

    public function __construct(
        string $defaultConnectionName,
        private RestorerFactory $restorerFactory,
        private BackupperFactory $backupperFactory,
        private AnonymizatorFactory $anonymizatorFactory,
        private DbToolsStorage $storage,
    ) {
        parent::__construct();

        $this->connectionName = $defaultConnectionName;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<TXT
                Anonymize a given backup file or the local database.

                This command will successively perform these steps:
                    1/ Backup the local database,
                    2/ Restore the given backup file,
                    3/ Anonymize the database,
                    4/ Backup the anonymized database by overwriting the given backup file,
                    5/ Restore the backup file produced at step 1.

                If called with the --local-database option, step 2 is skipped.
                If called with the --no-restore option, step 1 and 4 are skipped.
                TXT
            )
            ->addUsage('/path/to/backup/to/anonymize')
            ->addUsage('--local-database')
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Backup file to anonymize'
            )
            ->addOption(
                'local-database',
                null,
                InputOption::VALUE_NONE,
                'Anonymize local database instead of a given backup file'
            )
            ->addOption(
                'no-restore',
                null,
                InputOption::VALUE_NONE,
                'Do not restore local database after anonymization (skip step 1 and 4).'
            )
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'A doctrine connection name. If not given, use default connection'
            )
            ->addOption(
                'split-per-column',
                null,
                InputOption::VALUE_NONE,
                'During anonymization, when not set, a single UPDATE statement ' .
                'will be issued per table, when set, every target will issue its ' .
                'own UPDATE statement.'
            )
            ->addOption(
                'target',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Only anonymize the given column or table targets.',
            )
            ->addOption(
                'exclude',
                'x',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude given targets from anonymization.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->warning([
            'Note that this command should only be used in a secured environment. Following GDPR best ' .
            'practices, sensitive data should never transit on an unsecured environment.',
            'Learn how to use this command in a global GDPR-friendly workflow ' .
            'reading the DbToolsBundle documentation: ',
            '> https://dbtoolsbundle.readthedocs.io/en/stable/anonymization/workflow.html',
        ]);


        $this->doAnonymizeCurrentDatabase = !!$input->getOption('local-database');

        if ($input->getOption('no-restore')) {
            $this->doBackupAndRestoreInitial = false;
        }

        if (!$input->isInteractive()) {
            $this->io->warning("--no-interaction is set, no confirmation will be asked.");
        }

        $this->connectionName = $input->getOption('connection') ?? $this->connectionName;
        $this->backupFilename = $input->getArgument('filename');

        // Anonymization options
        $this->excludedTargets = $input->getOption('exclude');
        $this->onlyTargets = $input->getOption('target');
        $this->atOnce = !$input->getOption('split-per-column');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (!$this->backupFilename && !$this->doAnonymizeCurrentDatabase) {
            $this->doCancel = true;

            $this->io->caution([
                'You should either provide a backup file or use the --local-database option.',
                'For more information, launch this command with --help.'
            ]);

            return;
        }

        if (null === $this->doBackupAndRestoreInitial) {
            $this->doBackupAndRestoreInitial = $this->io->confirm("Do you want to backup local database and restore it at the end of this process?", true);
        }

        if ('prod' === $input->getOption('env') && !$this->io->confirm("You are currently on a production environment. Are you sure you want to continue?", false)) {
            $this->doCancel = true;

            return;
        }

        if (!$this->doBackupAndRestoreInitial) {
            $this->doCancel = $this->io->confirm("You are about to erase your local database. Are you sure you want to continue?", true);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ((false === $this->doBackupAndRestoreInitial) && ('prod' === $input->getOption('env'))) {
            $this->io->caution([
                "You are currently on a production environment.",
                "Anonymizing a local database in production is not allowed.",
            ]);

            return self::SUCCESS;
        }

        if ($this->doCancel) {
            $this->io->info("Action cancelled.");

            return self::SUCCESS;
        }

        try {
            if ($this->doBackupAndRestoreInitial) {
                $this->doBackupInitialDatabase();
            }

            if (!$this->doAnonymizeCurrentDatabase) {
                $this->doRestoreGivenBackup();
            }

            $this->doAnonymizeDatabase();
            $this->doBackupAnonymizedDatabase();

            if ($this->doBackupAndRestoreInitial) {
                $this->doRestoreInitialDatabase();
            }
        } catch (NotImplementedException $e) {
            $this->io->error($e->getMessage());

            return NotImplementedException::CONSOLE_EXIT_STATUS;
        }

        return Command::SUCCESS;
    }

    private function doBackupInitialDatabase(): void
    {
        $this->io->section('Start backuping local database');

        $backupper = $this->backupperFactory->create($this->connectionName);

        $this->initialDatabaseBackupFilename = $this->storage->generateFilename($this->connectionName, $backupper->getExtension());

        $backupper
            ->setDestination($this->initialDatabaseBackupFilename)
            ->setVerbose($this->io->isVerbose())
            ->startBackup()
        ;

        foreach ($backupper as $data) {
            if ($this->io->isVerbose()) {
                $this->io->text($data);
            }
        }

        $backupper->checkSuccessful();
        $this->io->text($backupper->getOutput());

        $this->io->newLine();
        $this->io->info("Backup of local database done: " . $this->backupFilename);
    }

    private function doRestoreGivenBackup(): void
    {
        $this->io->section('Start restoring given backup');

        $restorer = $this->restorerFactory->create($this->connectionName);

        $restorer
            ->setBackupFilename($this->backupFilename)
            ->setVerbose($this->io->isVerbose())
            ->startRestore()
        ;

        foreach ($restorer as $data) {
            if ($this->io->isVerbose()) {
                $this->io->text($data);
            }
        }

        $restorer->checkSuccessful();

        $this->io->text($restorer->getOutput());

        $this->io->newLine();
        $this->io->info("Restoration of given backup file done");
    }

    private function doAnonymizeDatabase(): void
    {
        $this->io->section('Start anonymizing database');

        $anonymizator = $this->anonymizatorFactory->getOrCreate($this->connectionName);

        $needsLineFeed = false;
        foreach ($anonymizator->anonymize($this->excludedTargets, $this->onlyTargets, $this->atOnce) as $message) {
            if ($this->io->isVerbose()) {
                if (\str_ends_with($message, '...')) {
                    $this->io->write($message);
                    $needsLineFeed = true;
                } elseif ($needsLineFeed) {
                    $this->io->writeln(' [' . $message . ']');
                    $needsLineFeed = false;
                } else {
                    $this->io->writeln($message);
                }
            }
        }
        if ($needsLineFeed) {
            $this->io->writeln("");
        }

        $this->io->newLine();
        $this->io->info("Database anonymized!");
    }

    private function doBackupAnonymizedDatabase(): void
    {
        $this->io->section('Start backuping anonymized database');

        $backupper = $this->backupperFactory->create($this->connectionName);
        // If we are not anomymizing a database from a given backup file, we put
        // anonymized database backup in classic storage dir but we specify
        // it's anonymized.
        $destination = $this->backupFilename ?? $this->storage->generateFilename($this->connectionName, $backupper->getExtension(), true);
        $backupper
            ->setDestination($destination)
            ->setVerbose($this->io->isVerbose())
            ->startBackup()
        ;

        foreach ($backupper as $data) {
            if ($this->io->isVerbose()) {
                $this->io->text($data);
            }
        }

        $backupper->checkSuccessful();
        $this->io->text($backupper->getOutput());

        $this->io->newLine();
        $this->io->success("Anonymized backup done : " . $this->backupFilename);
    }

    private function doRestoreInitialDatabase(): void
    {
        $this->io->section('Start restoring initial database');

        $restorer = $this->restorerFactory->create($this->connectionName);

        $restorer
            ->setBackupFilename($this->initialDatabaseBackupFilename)
            ->setVerbose($this->io->isVerbose())
            ->startRestore()
        ;

        foreach ($restorer as $data) {
            if ($this->io->isVerbose()) {
                $this->io->text($data);
            }
        }

        $restorer->checkSuccessful();

        $this->io->text($restorer->getOutput());

        $this->io->newLine();
        $this->io->info("Restoration done");
    }
}
