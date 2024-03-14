<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\DbToolsBundle\Helper\Output\ConsoleOutput;
use MakinaCorpus\DbToolsBundle\Restorer\AbstractRestorer;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use MakinaCorpus\DbToolsBundle\Storage\Storage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'db-tools:restore', description: 'Restore database')]
class RestoreCommand extends Command
{
    private SymfonyStyle $io;
    private string $connectionName;
    private AbstractRestorer $restorer;
    private ?string $backupFilename = null;
    private ?string $extraOptions = null;
    private bool $ignoreDefaultOptions = false;
    private $force = false;

    public function __construct(
        string $defaultConnectionName,
        private RestorerFactory $restorerFactory,
        private Storage $storage,
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
            ->setHelp('Restore database')
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'A doctrine connection name. If not given, use default connection'
            )
            ->addOption(
                'filename',
                null,
                InputOption::VALUE_OPTIONAL,
                'Skip backup file choice and restore given backup file'
            )
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'Only list existing backup files'
            )
            ->addOption(
                'extra-options',
                'o',
                InputOption::VALUE_REQUIRED,
                'Extra options to pass to the binary to perform the restoration, added to the default ones, unless you specify --ignore-default-options.'
            )
            ->addOption(
                '--ignore-default-options',
                null,
                InputOption::VALUE_NONE,
                'Ignore default options defined in the bundle configuration.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Do not ask for confirmation before restoring database'
            )
            ->addOption(
                'yes-i-am-sure-of-what-i-am-doing',
                null,
                InputOption::VALUE_NONE,
                'Use this option if you want to run this command in prod'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('connection')) {
            $this->connectionName = $input->getOption('connection');
        }
        if ($input->getOption('list')) {
            return $this->listBackups();
        }

        $this->extraOptions = $input->getOption('extra-options');
        $this->ignoreDefaultOptions = $input->getOption('ignore-default-options');

        if ($this->force = $input->getOption('force')) {
            $input->setInteractive(false);
        }

        $this->preventMistake($input);

        try {
            $this->restorer = $this->restorerFactory->create($this->connectionName);

            if (!$input->getOption('filename')) {
                $this->chooseBackup();
            } else {
                $this->backupFilename = $input->getOption('filename');
            }

            if (!$this->backupFilename) {
                return Command::INVALID;
            }

            $this->doRestore();
        } catch (NotImplementedException $e) {
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function preventMistake(InputInterface $input): void
    {
        if ('prod' == $input->getOption('env')) {
            $this->io->caution("You are in PROD, your action will destroy ALL actual production data!");
            if (!$this->force && !$input->getOption('yes-i-am-sure-of-what-i-am-doing')) {
                throw new \RuntimeException('If you want to run this command in production, use --yes-i-am-sure-of-what-i-am-doing option');
            }
            if (!$this->force && !$this->io->confirm("You are going to restore a database in production, are you sure you want to continue?!", false)) {
                throw new \RuntimeException('Action cancelled');
            } else {
                if (!$this->force && !$this->io->confirm("Well, this is dangerous, you confirm you want to restore a database in production ?", false)) {
                    throw new \RuntimeException('Action cancelled');
                }
            }
        }
    }

    private function doRestore(): void
    {
        $this->io->section('Starting restoration');

        $this->io->text("You are going to <error>destroy actual data</error> and restore <info>" . $this->backupFilename . "</info>");

        if (!$this->force && !$this->io->confirm("Are you sure you want to continue?", false)) {
            throw new \RuntimeException('Action cancelled');
        }

        $this
            ->restorer
            ->setBackupFilename($this->backupFilename)
            ->setExtraOptions($this->extraOptions)
            ->ignoreDefaultOptions($this->ignoreDefaultOptions)
            ->setOutput(new ConsoleOutput($this->io))
            ->setVerbose($this->io->isVerbose())
            ->execute()
        ;

        $this->io->success("Restoration done.");
    }

    private function chooseBackup()
    {
        $this->io->section('Backup choice');

        $backupLists = $this->storage->listBackups(
            $this->connectionName,
            $this->restorer->getExtension()
        );

        if (\count($backupLists)) {
            $options = \array_map(
                fn ($data) => (string)$data[1],
                $backupLists
            );

            $this->backupFilename = $this->io->choice(
                "Which backup do you want to restore?",
                $options,
                \array_key_last($options)
            );
        } else {
            $this->io->warning("There is no backup files available in " . $this->storage->getStoragePath());

            return false;
        }

        return true;
    }

    private function listBackups()
    {
        $this->io->section('Backups list');

        $backupLists = $this->storage->listBackups(
            $this->connectionName,
            $this->restorer->getExtension()
        );

        if (\count($backupLists)) {
            $options = \array_map(
                fn ($data) => (string)$data[1] . ' (' . $data[0] . ')',
                $backupLists
            );

            $this->io->listing($options);
        } else {
            $this->io->warning("There is no backup files available in " . $this->storage->getStoragePath());
        }

        return self::SUCCESS;
    }
}
