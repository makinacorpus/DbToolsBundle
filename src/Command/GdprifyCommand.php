<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'db-tools:gdprify',
    description: <<<TXT
    A GDPR-friendly workflow to import a production backup file.
    TXT,
)]
class GdprifyCommand extends Command
{
    private SymfonyStyle $io;
    private string $connectionName;
    private string $backupFilename = '';

    public function __construct(
        string $defaultConnectionName,
        private RestorerFactory $restorerFactory,
        private BackupperFactory $backupperFactory,
        private AnonymizatorFactory $anonymizatorFactory,
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
            ->setHelp(<<<TXT
            A GDPR-friendly workflow to import a production backup file.

            This command will successively perfom:
                - an import of the given backup file
                - an anonymization of the database
                - a backup of the newly anonymized database

            Note that the backup will overwrite the given file. This way, after this
            command, no sensitive data remain on your disk.
            TXT)
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'Backup file to import'
            )
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'A doctrine connection name. If not given, use default connection'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Do not ask for confirmation before restoring database'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $this->io = new SymfonyStyle($input, $output);

        $this->backupFilename = $input->getArgument('filename');

        if ($input->getOption('connection')) {
            $this->connectionName = $input->getOption('connection');
        }

        if ('prod' == $input->getOption('env')) {
            $this->io->caution("This command cannot be launched in production!");
        }

        if ($force = $input->getOption('force')) {
            $this->io->warning("--force is set, no confirmation will be asked.");

            $input->setInteractive(false);
        }

        if (!$force && !$this->io->confirm("Are you sure you want to launch GDPR workflow ? This will erase current data", false)) {
            $this->io->warning('Action cancelled');

            return self::FAILURE;
        }

        $this->connectionName = $input->getOption('connection') ?? $this->connectionName;

        try {
            $this->doRestore();
            $this->doAnonymize();
            $this->doBackup();
        } catch (NotImplementedException $e) {
            $this->io->error($e->getMessage());

            return NotImplementedException::CONSOLE_EXIT_STATUS;
        }

        return Command::SUCCESS;
    }

    private function doRestore(): void
    {
        $this->io->section('Starting restore');

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
        $this->io->success("Restoration done");
    }

    private function doAnonymize(): void
    {
        $this->io->section('Starting anonymization');

        $anonymizator = $this->anonymizatorFactory->getOrCreate($this->connectionName);

        $needsLineFeed = false;
        foreach ($anonymizator->anonymize() as $message) {
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
        $this->io->success("Database anonymized !");
    }

    private function doBackup(): void
    {
        $this->io->section('Starting backup');

        $backupper = $this->backupperFactory->create($this->connectionName);

        $backupper
            ->setDestination($this->backupFilename)
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
        $this->io->success("Backup done : " . $this->backupFilename);
    }
}
