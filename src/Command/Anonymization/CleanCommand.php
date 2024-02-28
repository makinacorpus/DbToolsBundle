<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command\Anonymization;

use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Helper\Output\ConsoleOutput;
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
        private AnonymizatorFactory $anonymizatorFactory,
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
            ->setDescription('Clean DbTools left-over temporary tables, columns and indexes.')
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
        $anonymizator = $this
            ->anonymizatorFactory
            ->getOrCreate($connectionName)
            ->setOutput(new ConsoleOutput($io))
        ;

        $garbage = $anonymizator->collectGarbage();

        if (!$garbage) {
            $io->success("There is no left-overs to clean, exiting.");

            return self::SUCCESS;
        }

        if (!$force) {
            $tables = $others = [];

            \array_walk($garbage, function ($item) use (&$tables, &$others) {
                if ('table' === $item['type']) {
                    $tables[] = $item['name'];
                } else {
                    $others[] = $item['table'] . '.' . $item['name'];
                }
            });

            $io->section("Items that will be deleted");
            $io->title('Tables');
            $io->listing($tables);
            $io->title('Columns and indexes');
            $io->listing($others);

            if (!$io->confirm("Are you sure you want to drop all these database items?", false)) {
                $io->warning('Action cancelled');

                return self::SUCCESS;
            }

            $io->section("Dropping items");
        }

        $anonymizator->clean();

        $io->newLine();
        $io->success("Clean done!");

        return Command::SUCCESS;
    }
}
