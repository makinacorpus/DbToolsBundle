<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command\Anonymization;

use MakinaCorpus\DbToolsBundle\Anonymization\AnonymizatorFactory;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'db-tools:anonymization:dump-config',
    description: 'Dump anonymization configuration.'
)]
class ConfigDumpCommand extends Command
{
    public function __construct(
        private AnonymizatorFactory $anonymizatorFactory,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new \LogicException('This command accepts only an instance of "ConsoleOutputInterface".');
        }

        $io = new SymfonyStyle($input, $output);

        foreach ($this->anonymizatorFactory->all() as $connectionName => $anonymizator) {

            $io->title('Connection: ' . $connectionName);

            $errors = $anonymizator->checkAnonymizationConfig();
            $config = $anonymizator->getAnonymizationConfig();
            foreach ($config->all() as $table => $tableConfig) {
                $io->section('Table: ' . $table);

                $tableErrors = $errors[$table] ?? [];
                $io->table(
                    ['', 'Target', 'Anonymizer', 'Options'],
                    \array_map(
                        fn (AnonymizerConfig $config) => [
                            \array_key_exists($config->targetName, $tableErrors) ? '<error>✘</>' : '<info>✔</>',
                            $config->targetName,
                            $config->anonymizer,
                            $config->options->toDisplayString() .
                            (
                                \key_exists($config->targetName, $tableErrors) ?
                                \PHP_EOL . '<error>' . $tableErrors[$config->targetName] . '</>'
                                : ''
                            ),
                        ],
                        $tableConfig,
                    )
                );
            }


        }

        return Command::SUCCESS;
    }
}
