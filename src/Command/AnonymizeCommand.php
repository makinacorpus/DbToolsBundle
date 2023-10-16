<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizatorRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
                'target',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Only anonymise the given column or table targets.',
                [],
            )
            ->addOption(
                'exclude',
                'x',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude given targets.'
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

        // Target identification.
        $excludedTargets = $input->getOption('exclude');
        $onlyTargets = $input->getOption('target');
        $atOnce = !$input->getOption('split-per-column');

        $anonymizator = $this->anonymizatorRegistry->get($this->connectionName);

        $needsLineFeed = false;
        foreach ($anonymizator->anonymize($excludedTargets, $onlyTargets, $atOnce) as $message) {
            if (\str_ends_with($message, '...')) {
                $output->write($message);
                $needsLineFeed = true;
            } else if ($needsLineFeed) {
                $output->writeln(' [' . $message . ']');
                $needsLineFeed = false;
            } else {
                $output->writeln($message);
            }
        }
        if ($needsLineFeed) {
            $output->writeln("");
        }

        $this->io->newLine();
        $this->io->success("Database anonymized !");

        return Command::SUCCESS;
    }
}
