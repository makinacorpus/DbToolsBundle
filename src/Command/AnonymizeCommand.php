<?php

namespace MakinaCorpus\DbToolsBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizatorRegistry;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

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

        $progressIndicator = new ProgressIndicator($output, 'very_verbose', 1, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('Starting database anonymization...');
        $total = $anonymizator->count();
        $count = 0;
        foreach ($anonymizator->anonymize($excludedTables) as $tableName => $names) {
            $count++;
            $progressIndicator->setMessage(\sprintf(
                "%s/%s - Anonymizing table <info>%s</info>: %s",
                $count,
                $total,
                $tableName,
                \implode(', ', $names)
            ));
            $progressIndicator->advance();
        }

        $progressIndicator->finish("Anonymization done");
        $this->io->newLine();

        $this->io->section("Cleaning");
        $this->io->text("Cleaning anonymizers...");
        $anonymizator->clean();
        $this->io->text("Cleaning done");

        $this->io->success("Database anonymized !");

        return Command::SUCCESS;
    }
}