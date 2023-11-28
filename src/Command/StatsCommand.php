<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Command;

use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\DbToolsBundle\Stats\AbstractStatsProvider;
use MakinaCorpus\DbToolsBundle\Stats\StatValue;
use MakinaCorpus\DbToolsBundle\Stats\StatValueList;
use MakinaCorpus\DbToolsBundle\Stats\StatsProviderFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'db-tools:stats', description: 'Give some database statistics')]
class StatsCommand extends Command
{
    public function __construct(
        private string $defaultConnectionName,
        private StatsProviderFactory $statsProviderFactory,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Give some database statistics')
            ->addArgument(
                'which',
                InputArgument::OPTIONAL,
                <<<TXT
                Which statistics to display. Allowed values are:
                  - "global": display some global statistics,
                  - "table": display per-table statistics, such as size, row count, ...
                  - "index": display per-index statistics, such as size, number of reads, ...
                TXT,
                'global',
            )
            ->addArgument(
                'connection',
                InputArgument::OPTIONAL,
                'A doctrine connection name. If not given, use default connection'
            )
            ->addOption(
                'flat',
                'f',
                InputOption::VALUE_NONE,
                'Display one line per value instead of using a table.',
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Show all values for all tags, ignores the --tag option.',
            )
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                <<<TXT
                Filter using given tags. Available tags are:
                  - "info": display global information,
                  - "read": read statistics,
                  - "write": write statistics,
                  - "maint": maintainance statistics, such as PostgreSQL VACUUM,
                  - "code": occasionaly display SQL code, such as CREATE statements.
                TXT,
                [StatValue::TAG_INFO, StatValue::TAG_READ],
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $connectionName = $input->getArgument('connection') ?? $this->defaultConnectionName;

        $tags = null;
        if (!$input->getOption('all') && ($rawTags = $input->getOption('tag'))) {
            $tags = $rawTags;
        }

        try {
            $statsProvider = $this->statsProviderFactory->create($connectionName);
        } catch (NotImplementedException $e) {
            $io->error($e->getMessage());

            return NotImplementedException::CONSOLE_EXIT_STATUS;
        }

        $which = $input->getArgument('which');

        $collections = match ($which) {
            'table' => $statsProvider->getTableStats($tags),
            'index' => $statsProvider->getIndexStats($tags),
            'global' => $statsProvider->getGlobalStats($tags),
            default => throw new InvalidArgumentException(\sprintf("'which' allowed values are: '%s'", \implode("', '", ['global', 'table', 'index']))),
        };

        $hasValues = false;

        if ($input->getOption('flat')) {
            $hasValues = $this->displayFlat($collections, $output);
        } else {
            $hasValues = $this->displayTable($collections, $output);
        }

        if (!$hasValues) {
            $io->warning(\sprintf("Statistics for '%s' are not supported for the current '%s' connexion database driver.", $which, $connectionName));
        }

        return Command::SUCCESS;
    }

    private function displayFlat(iterable $collections, OutputInterface $output): bool
    {
        $some = false;

        foreach ($collections as $collection) {
            \assert($collection instanceof StatValueList);

            $output->writeln($collection->name);
            $output->writeln(\str_repeat('-', \strlen($collection->name)));

            foreach ($collection as $value) {
                \assert($value instanceof StatValue);

                $prefix = '';
                if ($value->tags) {
                    $prefix .= "[" . \implode(', ', $value->tags) . '] ';
                }
                if ($unitStr = $value->unitToString()) {
                    $prefix .= "(" . $unitStr . ") ";
                }

                $output->writeln(\sprintf("%s%s: %s", $prefix, $value->name, $value->toString()));
            }

            $output->writeln("");

            if (!$some) {
                $some = true;
            }
        }

        return $some;
    }

    private function displayTable(iterable $collections, OutputInterface $output): bool
    {
        $rows = [];
        $headers = ["table"];
        $first = true;

        $style = clone Table::getStyleDefinition('symfony-style-guide');
        $style->setCellHeaderFormat('<info>%s</info>');
        $style->setPadType(\STR_PAD_LEFT);

        $alignLeftStyle = new TableCellStyle(['align' => 'left']);

        foreach ($collections as $collection) {
            \assert($collection instanceof StatValueList);

            $row = [
                // Align left table name.
                new TableCell($collection->name, ['style' => $alignLeftStyle]),
            ];

            foreach ($collection as $value) {
                \assert($value instanceof StatValue);

                if ($first) {
                    $header = $value->name;
                    if ($unitStr = $value->unitToString()) {
                        $header .= "\n(" . $unitStr . ")";
                    }
                    if ($value->tags) {
                        $header .= "\n" . "[" . \implode(', ', $value->tags) . ']';
                    }

                    $headers[] = $header;
                }

                if ($value->alignLeft()) {
                    $row[] = new TableCell($value->toString(), ['style' => $alignLeftStyle]);
                } else {
                    $row[] = $value->toString();
                }
            }

            $rows[] = $row;
            $first = false;
        }

        if (!$rows) {
            return false;
        }

        // Can't change pad style while using SymfonyStyle.
        (new Table($output))
            ->setStyle($style)
            ->setHeaders($headers)
            ->setRows($rows)
            ->render()
        ;

        $output->writeln("");

        return true;
    }
}
