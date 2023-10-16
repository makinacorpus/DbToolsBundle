<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Helper\Format;

class Anonymizator //extends \IteratorAggregate
{
    private array $anonymizationConfig = [];

    public function __construct(
        private string $connectionName,
        private Connection $connection,
        private AnonymizerRegistry $anonymizerRegistry
    ) {}

    /**
     * Add target anonymization configuration.
     */
    public function registerAnonymization(string $table, string $targetName, array $config): self
    {
        if (!isset($config['anonymizer'])) {
            throw new \InvalidArgumentException(\sprintf('Missing "anonymizer" for table "%s", key "%s"', $table, $targetName));
        }

        // Populate defaults.
        $config += [
            'target' => 'column',
            'options' => [],
        ];

        if (!$this->anonymizerRegistry->get($config['anonymizer'])) {
            throw new \InvalidArgumentException(\sprintf(
                'Can not find anonymizer "%s", check your anonymization configuration for table "%s", key "%s".',
                $config['anonymizer'],
                $table,
                $targetName
            ));
        }

        if (!isset($this->anonymizationConfig[$table])) {
            $this->anonymizationConfig[$table] = [];
        }
        $this->anonymizationConfig[$table][$targetName] = [
            'anonymizer' => $config['anonymizer'],
            'options' => new Options($config['options']),
        ];

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
     * Create anonymizer instance.
     */
    protected function createAnonymizer(string $name, string $tableName, string $targetName, array $config): AbstractAnonymizer
    {
        $className = $this->anonymizerRegistry->get($name);
        \assert(\is_subclass_of($className, AbstractAnonymizer::class));

        return new $className(
            $tableName,
            $targetName,
            $this->connection,
            $config['options'] ?? new Options(),
        );
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
     * @return \Generator<string>
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
            $initTimer = $this->startTimer();

            // Create anonymizer array prior running the anonymisation.
            $anonymizers = [];
            foreach ($this->getTableConfigTargets($table, $targets) as $target => $config) {
                if (!isset($config['anonymizer'])) {
                    throw new \InvalidArgumentException(\sprintf('Missing "anonymizer" for table "%s", key "%s"', $table, $target));
                }
                $anonymizers[] = $this->createAnonymizer($config['anonymizer'], $table, $target, $config);
            }

            try {
                yield \sprintf(' * table %d/%d: "%s" ("%s")', $count, $total, $table, \implode('", "', $targets));
                yield "   - initializing anonymizers...";
                \array_walk($anonymizers, fn (AbstractAnonymizer $anonymizer) => $anonymizer->initialize());
                yield $this->printTimer($initTimer);

                if ($atOnce) {
                    yield from $this->anonymizeTableAtOnce($table, $anonymizers);
                } else {
                    yield from $this->anonymizeTablePerColumn($table, $anonymizers);
                }
            } finally {
                $cleanTimer = $this->startTimer();
                // Cleanup everything, even in case of any error.
                yield "   - cleaning anonymizers...";
                \array_walk($anonymizers, fn (AbstractAnonymizer $anonymizer) => $anonymizer->clean());
                yield $this->printTimer($cleanTimer);
                yield '   - total ' . $this->printTimer($initTimer);
            }

            $count++;
        }
    }

    /**
     * Anonymize a single database table using a single UPDATE query for each.
     */
    protected function anonymizeTableAtOnce(string $table, array $anonymizers): \Generator
    {
        yield '   - anonymizing...';

        $timer = $this->startTimer();
        $platform = $this->connection->getDatabasePlatform();

        $updateQuery = $this
            ->connection
            ->createQueryBuilder()
            ->update($platform->quoteIdentifier($table))
        ;

        foreach ($anonymizers as $anonymizer) {
            \assert($anonymizer instanceof AbstractAnonymizer);
            $anonymizer->anonymize($updateQuery);
        }

        $updateQuery->executeQuery();

        yield $this->printTimer($timer);
    }

    /**
     * Anonymize a single database table using one UPDATE query per target.
     */
    protected function anonymizeTablePerColumn(string $table, array $anonymizers): \Generator
    {
        $platform = $this->connection->getDatabasePlatform();

        $total = \count($anonymizers);
        $count = 1;

        foreach ($anonymizers as $anonymizer) {
            \assert($anonymizer instanceof AbstractAnonymizer);

            $timer = $this->startTimer();

            $table = $anonymizer->getTableName();
            $target = $anonymizer->getColumnName();

            yield \sprintf('   - anonymizing %d/%d: "%s"."%s"...', $count, $total, $table, $target);

            $updateQuery = $this
                ->connection
                ->createQueryBuilder()
                ->update($platform->quoteIdentifier($table))
            ;

            $anonymizer->anonymize($updateQuery);

            $updateQuery->executeQuery();
            $count++;

            yield $this->printTimer($timer);
        }
    }

    protected function startTimer(): int|float
    {
        return \hrtime(true);
    }

    protected function printTimer(null|int|float $timer): string
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

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }
}
