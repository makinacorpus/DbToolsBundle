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
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\Query\DoctrineUpdate;

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

    protected function getQueryBuilder(): DoctrineQueryBuilder
    {
        return new DoctrineQueryBuilder($this->connection);
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

                $this->addAnonymizerIdColumn($table);

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

                $this->removeAnonymizerIdColumn($table);

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
                    $this->removeAnonymizerIdColumn($tableName);
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
        $update = $this->createUpdateQuery($table);

        foreach ($anonymizers as $anonymizer) {
            \assert($anonymizer instanceof AbstractAnonymizer);
            $anonymizer->anonymize($update);
        }

        $update->executeStatement();

        yield $this->printTimer($timer);
    }

    /**
     * Anonymize a single database table using one UPDATE query per target.
     */
    protected function anonymizeTablePerColumn(string $table, array $anonymizers): \Generator
    {
        $total = \count($anonymizers);
        $count = 1;

        foreach ($anonymizers as $anonymizer) {
            \assert($anonymizer instanceof AbstractAnonymizer);

            $timer = $this->startTimer();

            $table = $anonymizer->getTableName();
            $target = $anonymizer->getColumnName();

            yield \sprintf('   - anonymizing %d/%d: "%s"."%s"...', $count, $total, $table, $target);

            $update = $this->createUpdateQuery($table);
            $anonymizer->anonymize($update);
            $update->executeStatement();
            $count++;

            yield $this->printTimer($timer);
        }
    }

    protected function createUpdateQuery(string $table): DoctrineUpdate
    {
        $update = $this->getQueryBuilder()->update($table);

        // Add target table a second time into the FROM statement of the
        // UPDATE query, in order for anonymizers to be able to JOIN over
        // it. Otherwise, JOIN would not be possible for RDBMS that speak
        // standard SQL.
        $expr = $update->expression();
        $update->join(
            $table,
            $expr->where()->isEqual(
                $expr->column(AbstractAnonymizer::JOIN_ID, $table),
                $expr->column(AbstractAnonymizer::JOIN_ID, AbstractAnonymizer::JOIN_TABLE),
            ),
            AbstractAnonymizer::JOIN_TABLE
        );

        return $update;
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
     * This method will add a anonymizer identifier as a serial column on the
     * table to anonymize: having a serial will allow UPDATE "target_table" FROM
     * "sample_table" statements for injecting data randomly by joining over
     * ROW_NUMBER() using this serial column.
     *
     * @internal
     *   Public for unit testing only.
     */
    public function addAnonymizerIdColumn(string $table): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($schemaManager->listTableColumns($table) as $column) {
            \assert($column instanceof Column);

            if (AbstractAnonymizer::JOIN_ID === $column->getName()) {
                return;
            }
        }

        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform) {
            $this->addAnonymizerIdColumnMySql($table);

            return;
        }

        $schemaManager->alterSchema(
            new SchemaDiff(
                changedTables: [
                    new TableDiff(
                        tableName: $table,
                        addedColumns: [
                            new Column(
                                AbstractAnonymizer::JOIN_ID,
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
     * Remote anonymizer identifier column.
     */
    protected function removeAnonymizerIdColumn(string $tableName): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($schemaManager->listTableColumns($tableName) as $column) {
            \assert($column instanceof Column);

            if (AbstractAnonymizer::JOIN_ID === $column->getName()) {
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
    protected function addAnonymizerIdColumnMySql(string $table)
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
                                AbstractAnonymizer::JOIN_ID,
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

        $queryBuilder = $this->getQueryBuilder();

        $sequenceTableName = $platform->quoteIdentifier('_db_tools_seq_' . $table);
        $functionName = $platform->quoteIdentifier('_db_tools_seq_' . $table . '_get');

        $queryBuilder->executeStatement(
            <<<SQL
            DROP TABLE IF EXISTS ?::table
            SQL,
            [$sequenceTableName],
        );

        $queryBuilder->executeStatement(
            <<<SQL
            DROP FUNCTION IF EXISTS ?::identifier
            SQL,
            [$functionName],
        );

        $queryBuilder->executeStatement(
            <<<SQL
            CREATE TABLE ?::table (
                `value` BIGINT DEFAULT NULL
            );
            SQL,
            [$sequenceTableName],
        );

        $queryBuilder->executeStatement(
            <<<SQL
            INSERT INTO ?::table (`value`) VALUES (0);
            SQL,
            [$sequenceTableName],
        );

        try {
            $queryBuilder->executeStatement(
                <<<SQL
                CREATE FUNCTION IF NOT EXISTS ?::identifier() RETURNS BIGINT
                DETERMINISTIC
                BEGIN
                    SELECT `value` + 1 INTO @value FROM ?::table LIMIT 1;
                    UPDATE ?::table SET value = @value;
                    RETURN @value;
                END;
                ;;
                SQL,
                [
                    $functionName,
                    $sequenceTableName,
                    $sequenceTableName,
                ],
            );

            $queryBuilder->executeStatement(
                <<<SQL
                UPDATE ?::table SET ?::column = ?::identifier();
                SQL,
                [
                    $table,
                    AbstractAnonymizer::JOIN_ID,
                    $functionName,
                ],
            );

            $this->createIndex($table, AbstractAnonymizer::JOIN_ID);
        } finally {
            $queryBuilder->executeStatement(
                <<<SQL
                DROP FUNCTION IF EXISTS ?::identifier;
                SQL,
                [$functionName],
            );

            $queryBuilder->executeStatement(
                <<<SQL
                DROP TABLE IF EXISTS ?::table;
                SQL,
                [$sequenceTableName],
            );
        }
    }

    /**
     * Create an index.
     */
    protected function createIndex(string $table, string... $column): void
    {
        $indexName = $table . '_' . \implode('_', $column) . '_idx';
        $columnList = \implode(', ', \array_map(fn ($value) => '?', $column));

        $this->getQueryBuilder()->raw(
            <<<SQL
            CREATE INDEX ?::identifier ON ?::table ({$columnList})
            SQL,
            [$indexName, $table, ...$column]
        );
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
