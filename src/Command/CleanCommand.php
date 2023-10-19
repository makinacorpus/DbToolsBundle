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

#[AsCommand(name: 'db-tools:anonymization:clean', description: 'Clean DbTools left-over temporary tables')]
class CleanCommand extends Command
{
    public function __construct(
        private AnonymizatorRegistry $anonymizatorRegistry,
        private string $defaultConnectionName,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Clean DbTools left-over temporary tables')
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
                'Do not ask for confirmation before dropping database elements'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $io = new SymfonyStyle($input, $output);

        if ($force = $input->getOption('force')) {
            $io->warning("--force is set, no confirmation will be asked.");

            $input->setInteractive(false);
        }

        if ('prod' == $input->getOption('env')) {
            $io->caution("This command cannot be launched in production!");
        }

        $connectionName = $input->getOption('connection') ?? $this->defaultConnectionName;
        $anonymizator = $this->anonymizatorRegistry->get($connectionName);

        $items = \iterator_to_array($anonymizator->clean(true));

        if (!$items) {
            $io->success("There is is no left-overs to clean, exiting.");

            return self::SUCCESS;
        }

        if (!$force) {
            $io->section("Items that will be deleted");
            $io->listing($items);

            if (!$io->confirm("Are you sure you want to drop all this database items?", false)) {
                $io->warning('Action cancelled');

                return self::FAILURE;
            }

            $io->section("Dropping items");
        }

        foreach ($anonymizator->clean(false) as $message) {
            $io->writeln('Dropping ' . $message);
        }

        $io->newLine();
        $io->success("Clean done!");

        return Command::SUCCESS;
    }
}
