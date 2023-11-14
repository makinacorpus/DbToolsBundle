<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use MakinaCorpus\DbToolsBundle\Helper\Format;

class Anonymizator
{
    public function __construct(
        private Connection $connection,
        private AnonymizerRegistry $anonymizerRegistry,
        private AnonymizationConfig $anonymizationConfig,
    ) {}

    /**
     * Count tables.
     */
    public function count(): int
    {
        return $this->anonymizationConfig->count();
    }

    /**
     * Create anonymizer instance.
     */
    protected function createAnonymizer(AnonymizerConfig $config): AbstractAnonymizer
    {
        $className = $this->anonymizerRegistry->get($config->anonymizer);
        \assert(\is_subclass_of($className, AbstractAnonymizer::class));

        return new $className(
            $config->table,
            $config->targetName,
            $this->connection,
            $config->options,
        );
    }

    public function getAnonymizationConfig(): AnonymizationConfig
    {
        return $this->anonymizationConfig;
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
                    list($table, $target) = \explode('.', $targetString, 2);
                    $plan[$table][] = $target; // Avoid duplicates.
                } else {
                    $plan[$targetString] = [];
                }
            }
        } else {
            foreach ($this->anonymizationConfig->all() as $tableName => $config) {
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
            foreach ($this->anonymizationConfig->getTableConfig($table, $targets) as $target => $config) {
                $anonymizers[] = $this->createAnonymizer($config);
            }

            try {
                yield \sprintf(' * table %d/%d: "%s" ("%s")', $count, $total, $table, \implode('", "', $targets));
                yield "   - initializing anonymizers...";
                \array_walk($anonymizers, fn (AbstractAnonymizer $anonymizer) => $anonymizer->initialize());
                yield $this->printTimer($initTimer);

                $this->addSerialColumn($table);

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

                $this->removeSerialColumn($tableName);

                yield $this->printTimer($cleanTimer);
                yield '   - total ' . $this->printTimer($initTimer);
            }

            $count++;
        }
    }

    /**
     * Forcefuly clean all left-over temporary tables.
     *
     * This method could be dangerous if you have table names whose name matches
     * the temporary tables generated by this bundle, hence the dry run mode
     * being the default.
     *
     * This method yields all symbol names that will be dropped from the database.
     */
    public function clean(bool $dryRun = true): \Generator
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($schemaManager->listTableNames() as $tableName) {
            if (\str_starts_with($tableName, AbstractAnonymizer::TEMP_TABLE_PREFIX)) {

                yield \sprintf("table: %s", $tableName);

                if (!$dryRun) {
                    $schemaManager->dropTable($tableName);
                }
            } else {
                if (!$dryRun) {
                    $this->removeSerialColumn($tableName);
                }
            }
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

    /**
     * This method will add a serial column on the table to anonymize: having a
     * serial will allow UPDATE "target_table" FROM "sample_table" statements
     * for injecting data randomly by joining over ROW_NUMBER() using this
     * serial column.
     *
     * @internal
     *   Public for unit testing only.
     */
    public function addSerialColumn(string $table): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($schemaManager->listTableColumns($table) as $column) {
            \assert($column instanceof Column);

            if ('_anonymizer_id' === $column->getName()) {
                return;
            }
        }

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform || $platform instanceof MariaDBPlatform) {
            $this->addSerialColumnMySql($table);

            return;
        }

        $schemaManager->alterSchema(
            new SchemaDiff(
                changedTables: [
                    new TableDiff(
                        tableName: $table,
                        addedColumns: [
                            new Column(
                                '_anonymizer_id',
                                Type::getType(Types::BIGINT),
                                [
                                    'autoincrement' => true,
                                ],
                            ),
                        ],
                    ),
                ],
            ),
        );
    }

    /**
     * Remote serial column.
     */
    protected function removeSerialColumn(string $tableName): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($schemaManager->listTableColumns($tableName) as $column) {
            \assert($column instanceof Column);

            if ('_anonymizer_id' === $column->getName()) {
                $this->dropColumn($tableName, $column->getName());
            }
        }
    }

    /**
     * We are adding an arbitrary integer based identity column, MySQL only
     * support this using AUTO_INCREMENT, and it can only exist one per table.
     *
     * Because we don't known anything about the target table, we can't add an
     * AUTO_INCREMENT, we are else going to create a BIGINT colum, which default
     * value is NULL, and populate it using a custom function that creates as
     * sequence.
     *
     * By chance, MySQL doesn't cache function calls for using a single result
     * in UPDATE queries, so updating a single column using a function call
     * actually call the function for each row, which makes us a shiny sequence.
     *
     * Since this code is for MySQL only, we can therefore use raw SQL using
     * MySQL syntax directly without fear. MariaDB will work as well since it's
     * MySQL compatible.
     *
     * All other RDBMS should be fine with more than one identity columns per
     * table.
     */
    protected function addSerialColumnMySql(string $table)
    {
        $schemaManager = $this->connection->createSchemaManager();
        $platform = $this->connection->getDatabasePlatform();

        $schemaManager->alterSchema(
            new SchemaDiff(
                changedTables: [
                    new TableDiff(
                        tableName: $table,
                        addedColumns: [
                            new Column(
                                '_anonymizer_id',
                                Type::getType(Types::BIGINT),
                                [
                                    'autoincrement' => false,
                                    'notnull' => false,
                                    'default' => null,
                                ],
                            ),
                        ],
                    ),
                ],
            ),
        );

        $escapedTableName = $platform->quoteIdentifier($table);
        $escapedSequenceTableName = $platform->quoteIdentifier('_anonymizer_seq_' . $table);
        $escapedFunctionName = $platform->quoteIdentifier('_anonymizer_seq_' . $table . '_get');

        $this->connection->executeStatement(
            <<<SQL
            DROP TABLE IF EXISTS {$escapedSequenceTableName};
            SQL
        );
        $this->connection->executeStatement(
            <<<SQL
            DROP FUNCTION IF EXISTS {$escapedFunctionName};
            SQL
        );

        $this->connection->executeStatement(
            <<<SQL
            CREATE TABLE {$escapedSequenceTableName} (
                `value` BIGINT DEFAULT NULL
            );
            SQL
        );

        $this->connection->executeStatement(
            <<<SQL
            INSERT INTO {$escapedSequenceTableName} (`value`) VALUES (0);
            SQL
        );

        try {
            $this->connection->executeStatement(
                <<<SQL
                CREATE FUNCTION IF NOT EXISTS {$escapedFunctionName}() RETURNS BIGINT
                DETERMINISTIC
                BEGIN
                    SELECT `value` + 1 INTO @value FROM {$escapedSequenceTableName} LIMIT 1;
                    UPDATE {$escapedSequenceTableName} SET value = @value;
                    RETURN @value;
                END;
                ;;
                SQL
            );

            $this->connection->executeStatement(
                <<<SQL
                UPDATE {$escapedTableName}
                SET
                    `_anonymizer_id` = {$escapedFunctionName}();
                SQL
            );
        } finally {
            $this->connection->executeStatement(
                <<<SQL
                DROP FUNCTION IF EXISTS {$escapedFunctionName};
                SQL
            );
            $this->connection->executeStatement(
                <<<SQL
                DROP TABLE IF EXISTS {$escapedSequenceTableName};
                SQL
            );
        }
    }

    /**
     * Drop a single table column.
     */
    protected function dropColumn(string $tableName, string $columnName): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($schemaManager->listTableColumns($tableName) as $column) {
            \assert($column instanceof Column);

            if ($column->getName() === $columnName) {
                $schemaManager
                    ->alterSchema(
                        new SchemaDiff(
                            changedTables: [
                                new TableDiff(
                                    tableName: $tableName,
                                    droppedColumns: [$column],
                                ),
                            ],
                        ),
                    )
                ;
            }
        }
    }

    public function getConnectionName(): string
    {
        return $this->anonymizationConfig->connectionName;
    }

    public function checkConfig(): void
    {
        foreach ($this->anonymizationConfig->all() as $tableConfig) {
            foreach ($tableConfig as $config) {
                $this->createAnonymizer($config);
            }
        }
    }
}
