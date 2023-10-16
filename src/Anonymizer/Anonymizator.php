<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target\Column;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target\Table;
use MakinaCorpus\DbToolsBundle\Helper\Format;

class Anonymizator //extends \IteratorAggregate
{
    private array $anonymizationConfig = [];

    /** @var AbstractAnonymizer[] */
    private array $anonymizers = [];

    public function __construct(
        private string $connectionName,
        private Connection $connection,
        private AnonymizerRegistry $anonymizerRegistry
    ) {}

    public function addAnonymization(string $table, string $targetName, array $config): self
    {
        if (!isset($config['anonymizer'])) {
            throw new \InvalidArgumentException(\sprintf('Missing "anonymizer" for table "%s", key "%s"', $table, $targetName));
        }

        // Populate defaults.
        $config += [
            'target' => 'column',
            'options' => [],
        ];

        if (!$anonymizer = $this->anonymizerRegistry->get($config['anonymizer'])) {
            throw new \InvalidArgumentException(\sprintf(
                'Can not find anonymizer "%s", check your anonymization configuration for table "%s", key "%s".',
                $config['anonymizer'],
                $table,
                $targetName
            ));
        }

        $target = match($config['target']) {
            'table' => new Table($table),
            'column' => new Column($table, $targetName),
            default => throw new \InvalidArgumentException(\sprintf('Table "%s", key "%s": target option "%s" is unknown. Available options are: table, column', $table, $targetName, $config['target'])),
        };

        if (!isset($this->anonymizationConfig[$table])) {
            $this->anonymizationConfig[$table] = [];
        }
        $this->anonymizationConfig[$table][$targetName] = [
            'anonymizer' => $config['anonymizer'],
            'target' => $target,
            'options' => new Options($config['options']),
        ];

        if (!isset($this->anonymizers[$config['anonymizer']])) {
            $this->anonymizers[$config['anonymizer']] = new $anonymizer($this->connection);
        }

        return $this;
    }

    /**
     * Initialize all anonymizers.
     */
    public function initialize(): self
    {
        foreach($this->anonymizers as $anonymizer) {
            $anonymizer->initialize();
        }

        return $this;
    }

    /**
     * Count tables.
     */
    public function count(): int
    {
        return \count($this->anonymizationConfig);
    }

    /**
     * Get a single table configuration.
     */
    protected function getTableConfig(string $table): array
    {
        return $this->anonymizationConfig[$table] ?? throw new \InvalidArgumentException(\sprintf(
            "Table '%s' does not exist in configuration",
            $table
        ));
    }

    /**
     * Get all columns or table targets for a given table.
     *
     * @param null|array $targets
     *   Restrict return to the given target names.
     */
    protected function getTableConfigTargets(string $table, ?array $targets = null): array
    {
        $config = $this->getTableConfig($table);

        if ($targets) {
            $ret = [];
            foreach ($targets as $target) {
                $ret[$target] = $config[$target] ?? throw new \InvalidArgumentException(\sprintf(
                    "Target '%s'.'%s' does not exist in configuration",
                    $table,
                    $target
                ));
            }

            return $ret;
        }

        return $config;
    }

    /**
     * Anonymize all configured database tables.
     *
     * @param null|array<string> $excludedTargets
     *   Exclude targets:
     *     - "TABLE_NAME" for a complete table,
     *     - "TABLE_NAME.TARGET_NAME" for a single table column.
     * @param null|array<string> $onlyTargets
     *   Filter and proceed only those targets:
     *     - "TABLE_NAME" for a complete table,
     *     - "TABLE_NAME.TARGET_NAME" for a single table column.
     * @param bool $atOnce
     *   If set to false, there will be one UPDATE query per anonymizer, if set
     *   to true a single UPDATE query for anonymizing all at once will be done.
     *
     * @return \Iterator<string>
     *   Progression messages.
     */
    public function anonymize(
        ?array $excludedTargets = null,
        ?array $onlyTargets = null,
        bool $atOnce = true
    ): \Generator {

        if ($excludedTargets && $onlyTargets) {
            throw new \InvalidArgumentException("\$excludedTargets and \$onlyTargets are mutually exclusive.");
        }

        $plan = [];

        if ($onlyTargets) {
            foreach ($onlyTargets as $targetString) {
                if (\str_contains($targetString, '.')) {
                    list ($table, $target) = \explode('.', $targetString, 2);
                    $plan[$table][] = $target; // Avoid duplicates.
                } else {
                    $plan[$targetString] = [];
                }
            }
        } else {
            foreach ($this->anonymizationConfig as $tableName => $config) {
                if ($excludedTargets && \in_array($tableName, $excludedTargets)) {
                    continue; // Whole table is ignored.
                }
                foreach (\array_keys($config) as $targetName) {
                    if ($excludedTargets && \in_array($tableName . '.' . $targetName, $excludedTargets)) {
                        continue; // Column is excluded.
                    }
                    $plan[$tableName][] = $targetName;
                }
            }
        }

        $total = \count($plan);
        $count = 1;

        foreach ($plan as $table => $targets) {
            $timer = $this->startTimer();

            if ($atOnce) {
                if (!$targets) {
                    $targets = \array_keys($this->getTableConfig($table));
                }
                yield \sprintf(' * table %d/%d: "%s" ("%s")...', $count, $total, $table, \implode('", "', $targets));
                yield from $this->anonymizeTableAtOnce($table, $targets);
                yield $this->stopTimer($timer);
            } else {
                yield \sprintf(' * table %d/%d: "%s":', $count, $total, $table);
                yield from $this->anonymizeTablePerColumn($table, $targets);
                yield '   ' . $this->stopTimer($timer);
            }
            $count++;
        }
    }

    /**
     * Anonymize a single database table using a single UPDATE query for each.
     */
    protected function anonymizeTableAtOnce(string $table, ?array $targets = null): \Generator
    {
        $platform = $this->connection->getDatabasePlatform();

        $updateQuery = $this->connection
            ->createQueryBuilder()
            ->update($platform->quoteIdentifier($table))
        ;

        foreach ($this->getTableConfigTargets($table, $targets) as $target => $config) {
            if (!isset($config['anonymizer'])) {
                throw new \InvalidArgumentException(\sprintf('Missing "anonymizer" for table "%s", key "%s"', $table, $target));
            }

            $this->anonymizers[$config['anonymizer']]->anonymize(
                $updateQuery,
                $config['target'],
                $config['options']
            );
        }

        $updateQuery->executeQuery();

        yield from []; // Keep signature, avoid crash.
    }

    /**
     * Anonymize a single database table using one UPDATE query per target.
     */
    protected function anonymizeTablePerColumn(string $table, ?array $targets = null, ?string $progress = null): \Generator
    {
        $platform = $this->connection->getDatabasePlatform();

        $targets = $this->getTableConfigTargets($table, $targets);

        $total = \count($targets);
        $count = 1;

        foreach ($targets as $target => $config) {
            $timer = $this->startTimer();

            yield \sprintf('   - target %d/%d: "%s"."%s"...', $count, $total, $table, $target);

            $updateQuery = $this->connection
                ->createQueryBuilder()
                ->update($platform->quoteIdentifier($table))
            ;

            if (!isset($config['anonymizer'])) {
                throw new \InvalidArgumentException(\sprintf('Missing "anonymizer" for table "%s", key "%s"', $table, $target));
            }

            $this->anonymizers[$config['anonymizer']]->anonymize(
                $updateQuery,
                $config['target'],
                $config['options']
            );

            $updateQuery->executeQuery();
            $count++;

            yield $this->stopTimer($timer);
        }
    }

    protected function startTimer(): int|float
    {
        return \hrtime(true);
    }

    protected function stopTimer(null|int|float $timer): string
    {
        if (null === $timer) {
            return 'N/A';
        }

        return \sprintf(
            "time: %s, mem: %s",
            Format::time((\hrtime(true) - $timer) / 1e+6),
            Format::memory(\memory_get_usage(true)),
        );
    }

    /**
     * Clean all anonymizers
     */
    public function clean(): self
    {
        foreach($this->anonymizers as $anonymizer) {
            $anonymizer->clean();
        }

        return $this;
    }

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }
}
