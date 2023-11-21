<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'db-tools:anonymization:run', description: 'Anonymize database', aliases: ['db-tools:anonymize'])]
class AnonymizeCommand extends Command
{
    public function __construct(
        private AnonymizatorFactory $anonymizatorFactory,
        private string $defaultConnectionName,
    ) {
        parent::__construct();
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

        $io = new SymfonyStyle($input, $output);

        if ('prod' == $input->getOption('env')) {
            $io->caution("This command cannot be launched in production!");
        }

        if ($force = $input->getOption('force')) {
            $io->warning("--force is set, no confirmation will be asked.");

            $input->setInteractive(false);
        }

        if (!$force && !$io->confirm("Are you sure you want to anonymize your database?", false)) {
            $io->warning('Action cancelled');

            return self::FAILURE;
        }

        // Target identification.
        $excludedTargets = $input->getOption('exclude');
        $onlyTargets = $input->getOption('target');
        $atOnce = !$input->getOption('split-per-column');

        $connectionName = $input->getOption('connection') ?? $this->defaultConnectionName;
        $anonymizator = $this->anonymizatorFactory->getOrCreate($connectionName);

        $needsLineFeed = false;
        foreach ($anonymizator->anonymize($excludedTargets, $onlyTargets, $atOnce) as $message) {
            if (\str_ends_with($message, '...')) {
                $io->write($message);
                $needsLineFeed = true;
            } elseif ($needsLineFeed) {
                $io->writeln(' [' . $message . ']');
                $needsLineFeed = false;
            } else {
                $io->writeln($message);
            }
        }
        if ($needsLineFeed) {
            $io->writeln("");
        }

        $io->newLine();
        $io->success("Database anonymized !");

        return Command::SUCCESS;
    }
}
