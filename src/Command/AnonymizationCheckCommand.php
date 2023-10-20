<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationSingleConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizatorRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'db-tools:anonymization:check', description: 'Check and dump anonymization configuration.')]
class AnonymizationCheckCommand extends Command
{
    public function __construct(
        private AnonymizatorRegistry $anonymizatorRegistry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $io = new SymfonyStyle($input, $output);

        foreach ($this->anonymizatorRegistry->all() as $connectionName => $anonymizator) {
            $io->title('Connection: ' . $connectionName);

            $anonymizator->checkConfig();
            $config = $anonymizator->getAnonymizationConfig();
            foreach ($config->all() as $table => $tableConfig) {
                $io->section('Table: ' . $table);

                $io->table(
                    ['Target', 'Anonymizer', 'Options'],
                    \array_map(
                        fn (AnonymizationSingleConfig $config) => [
                            $config->targetName,
                            $config->anonymizer,
                            (string) $config->options
                        ],
                        $tableConfig,
                    )
                );
            }

        }

        return Command::SUCCESS;
    }
}
