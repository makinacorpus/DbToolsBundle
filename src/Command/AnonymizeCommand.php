<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizatorRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'db-tools:anonymize', description: 'Anonymize database', aliases: ['dbt:a'])]
class AnonymizeCommand extends Command
{
    private SymfonyStyle $io;
    private string $connectionName;

    public function __construct(
        private AnonymizatorRegistry $anonymizatorRegistry,
        string $defaultConnectionName,
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
            ->setHelp('Anonymize database')
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_OPTIONAL,
                'A doctrine connection name. If not given, use default connection'
            )
            ->addOption(
                'split-per-column',
                's',
                InputOption::VALUE_NONE,
                'When not set, a single UPDATE statement will be issued per table, when set, every target will issue its own UPDATE statement.'
            )
            ->addOption(
                'excluded-tables',
                null,
                InputOption::VALUE_REQUIRED,
                'Tables to exclude from anonymization, separate with comma (ex: users,logs).'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Do not ask for confirmation before restoring database'
            )
        ;
    }

    private function startTimer(): int|float
    {
        return \hrtime(true);
    }

    private function stopTimer(null|int|float $timer): string
    {
        if (null !== $timer) {
            return \sprintf(
                "%s %s",
                Helper::formatTime((\hrtime(true) - $timer) / 1e+9),
                Helper::formatMemory(\memory_get_usage(true)),
            );
        }
        return 'N/A';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        if ($force = $input->getOption('force')) {
            $input->setInteractive(false);
        }

        $this->io = new SymfonyStyle($input, $output);

        if ('prod' == $input->getOption('env')) {
            $this->io->caution("This command cannot be launched in production!");
        }

        if (!$force && !$this->io->confirm("Are you sure you want to anonymize your database?", false)) {
            throw new \RuntimeException('Action cancelled');
        }

        if ($input->getOption('connection')) {
            $this->connectionName = $input->getOption('connection');
        }

        $excludedTables = $input->getOption('excluded-tables');

        $anonymizator = $this->anonymizatorRegistry->get($this->connectionName);

        $this->io->section("Initialization");
        $this->io->text("Initializing anonymizers...");
        $anonymizator->initialize();
        $this->io->text("Initialization done");

        $this->io->section("Anonymization");

        $totalTimer = $this->startTimer();

        if (!$input->getOption('split-per-column')) {
            $output->writeln("");
            $total = $anonymizator->count();
            $count = 0;

            $timer = null;
            foreach ($anonymizator->anonymize($excludedTables, true) as $tableName => $config) {
                if ($timer) {
                    $output->writeln('' . $this->stopTimer($timer));
                }
                $timer = $this->startTimer();
                $count++;

                $output->write(\sprintf( '%d/%d - table "%s" ("%s")...', $count, $total, $tableName, \implode('", "', \array_keys($config))));
            }
            if ($count) {
                $output->writeln('' . $this->stopTimer($timer));
                $output->writeln("");
            }
        } else {
            $total = $anonymizator->count();
            $count = 0;

            $timer = null;
            $previousTable = null;
            $tableTimer = null;
            foreach ($anonymizator->anonymize($excludedTables, false) as $targetName => $config) {
                if ($timer) {
                    $output->writeln(' ' . $this->stopTimer($timer));
                }
                $timer = $this->startTimer();

                $tableName = $config['table'];
                if ($previousTable !== $tableName) {
                    if ($tableTimer) {
                        $output->writeln(\sprintf('  - total for "%s": %s', $previousTable, $this->stopTimer($tableTimer)));
                    }
                    $tableTimer = $this->startTimer();
                    $count++;
                    $output->writeln(\sprintf('%d/%d - table "%s":', $count, $total, $tableName));
                    $previousTable = $tableName;
                }
                $output->write(\sprintf('  - target/column "%s"."%s"...', $tableName, $targetName, ));
            }
            if ($count) {
                $output->writeln(' ' . $this->stopTimer($timer));
                $output->writeln("");
            }
            if ($previousTable) {
                $output->writeln(\sprintf('  - total for "%s": %s', $previousTable, $this->stopTimer($tableTimer)));
            }
        }

        $this->io->text(\sprintf("Total: %s", $this->stopTimer($totalTimer)));

        $this->io->newLine();

        $this->io->section("Cleaning");
        $this->io->text("Cleaning anonymizers...");
        $anonymizator->clean();
        $this->io->text("Cleaning done");

        $this->io->success("Database anonymized !");

        return Command::SUCCESS;
    }
}
