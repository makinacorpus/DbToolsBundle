<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Helper\Format;
use MakinaCorpus\DbToolsBundle\Helper\Log\ChainLoggerAwareTrait;
use MakinaCorpus\DbToolsBundle\Helper\Output\NullOutput;
use MakinaCorpus\DbToolsBundle\Helper\Output\OutputInterface;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Query\Update;

class Anonymizator
{
    use ChainLoggerAwareTrait;

    private OutputInterface $output;

    public function __construct(
        private Connection $connection,
        private AnonymizerRegistry $anonymizerRegistry,
        private AnonymizationConfig $anonymizationConfig,
        private ?string $salt = null,
    ) {
        $this->output = new NullOutput();
    }

    /**
     * Count tables.
     */
    public function count(): int
    {
        return $this->anonymizationConfig->count();
    }

    /**
     * Set the output handler.
     */
    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public static function generateRandomSalt(): string
    {
        return \base64_encode(\random_bytes(12));
    }

    protected function getSalt(): string
    {
        return $this->salt ??= self::generateRandomSalt();
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
            $config->options->with(['salt' => $this->getSalt()]),
        );
    }

    public function getAnonymizationConfig(): AnonymizationConfig
    {
        return $this->anonymizationConfig;
    }

    /**
     * Anonymize all configured database tables.
     *
     * @param null|string[] $excludedTargets
     *   Exclude targets:
     *     - "TABLE_NAME" for a complete table,
     *     - "TABLE_NAME.TARGET_NAME" for a single table column.
     * @param null|string[] $onlyTargets
     *   Filter and proceed only those targets:
     *     - "TABLE_NAME" for a complete table,
     *     - "TABLE_NAME.TARGET_NAME" for a single table column.
     * @param bool $atOnce
     *   If set to false, there will be one UPDATE query per anonymizer, if set
     *   to true a single UPDATE query for anonymizing all at once will be done.
     */
    public function anonymize(
        ?array $excludedTargets = null,
        ?array $onlyTargets = null,
        bool $atOnce = true
    ): void {

        if ($excludedTargets && $onlyTargets) {
            throw new \InvalidArgumentException("\$excludedTargets and \$onlyTargets are mutually exclusive.");
        }

        $plan = [];

        if ($onlyTargets) {
            foreach ($onlyTargets as $targetString) {
                if (\str_contains($targetString, '.')) {
                    [$table, $target] = \explode('.', $targetString, 2);
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
            // Base context for logging.
            $context = [
                'table' => $table,
                'targets' => \implode('", "', $targets),
            ];

            $initTimer = $this->startTimer();

            // Create anonymizer array prior running the anonymisation.
            $anonymizers = [];
            foreach ($this->anonymizationConfig->getTableConfig($table, $targets) as $target => $config) {
                $anonymizers[] = $this->createAnonymizer($config);
            }

            try {
                $this->output->writeLine(\sprintf(
                    ' * table %d/%d: "%s" ("%s")',
                    $count,
                    $total,
                    $table,
                    \implode('", "', $targets)
                ));

                $this->output->indent();
                $this->output->write("- initializing anonymizers...");

                \array_walk($anonymizers, fn (AbstractAnonymizer $anonymizer) => $anonymizer->initialize());

                $timer = $this->formatTimer($initTimer);
                $this->output->writeLine(\sprintf('[%s]', $timer));

                $this->addAnonymizerIdColumn($table);

                if ($atOnce) {
                    $this->anonymizeTableAtOnce($table, $anonymizers);
                    $this->getChainLogger()->info(
                        'Table "{table}" anonymized at once. Targets were: "{targets}" ({timer}).',
                        $context + ['timer' => $timer]
                    );
                }
                else {
                    $this->anonymizeTablePerColumn($table, $anonymizers);
                }
            }
            catch (\Throwable $e) {
                $this->getChainLogger()->error(
                    'Exception caught when anonymizing "{table}" table: {error}. (Targets were: "{targets}").',
                    $context + ['error' => $e->getMessage()]
                );
            }
            finally {
                $cleanTimer = $this->startTimer();
                // Clean up everything, even in case of any error.
                $this->output->write("- cleaning anonymizers...");
                \array_walk($anonymizers, fn (AbstractAnonymizer $anonymizer) => $anonymizer->clean());

                $this->removeAnonymizerIdColumn($table);

                $cleanTimer = $this->formatTimer($cleanTimer);
                $this->output->writeLine(\sprintf('[%s]', $cleanTimer));
                $this->output->write("- total " . $this->formatTimer($initTimer));
                $this->output->outdent();

                $this->getChainLogger()->info(
                    'Clean-up performed after anonymizing "{table}" table ({timer}).',
                    $context + ['timer' => $cleanTimer]
                );
            }

            $count++;
        }
    }

    /**
     * Forcefully clean all leftover temporary tables, columns and indexes.
     */
    public function clean(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->collectGarbage() as $item) {
            switch ($item['type']) {
                case 'table':
                    $this->output->writeLine(\sprintf(
                        "Dropping table: %s", $item['name']
                    ));
                    $schemaManager->dropTable($item['name']);
                    break;

                case 'column':
                    $this->output->writeLine(\sprintf(
                        "Dropping column: %s", $item['table'] . '.' . $item['name']
                    ));
                    $this->dropColumn($item['table'], $item['name']);
                    break;

                case 'index':
                    $this->output->writeLine(\sprintf(
                        "Dropping index: %s", $item['table'] . '.' . $item['name']
                    ));
                    $this->dropIndex($item['table'], $item['name']);
                    break;

                default:
                    throw new \DomainException(\sprintf(
                        'Unsupported "%s" structure type.', $item['type']
                    ));
            }
        }
    }

    /**
     *
     * @return array<array<string, string>>
     *   Each item is an array structured as such:
     *   [
     *     'type' => 'table' / 'column' / 'index',
     *     'name' => 'table_column_or_index_name',
     *     'table' => 'table_name' (only for columns and indexes)
     *   ]
     */
    public function collectGarbage(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $garbage = [];

        foreach ($schemaManager->listTableNames() as $tableName) {
            if (\str_starts_with($tableName, AbstractAnonymizer::TEMP_TABLE_PREFIX)) {
                $garbage[] = ['type' => 'table', 'name' => $tableName];
            } else {
                $garbage = \array_merge($garbage, $this->collectGarbageInto($tableName));
            }
        }

        return $garbage;
    }

    protected function collectGarbageInto(string $table): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $garbage = [];

        // List indexes to remove before columns to avoid problems with
        // SQL Server which doesn't support to drop a column still used
        // by an index.
        foreach ($schemaManager->listTableIndexes($table) as $index) {
            \assert($index instanceof Index);
            if (AbstractAnonymizer::JOIN_ID_INDEX === $index->getName()) {
                $garbage[] = [
                    'type' => 'index',
                    'name' => $index->getName(),
                    'table' => $table,
                ];
            }
        }

        foreach ($schemaManager->listTableColumns($table) as $column) {
            \assert($column instanceof Column);
            if (AbstractAnonymizer::JOIN_ID === $column->getName()) {
                $garbage[] = [
                    'type' => 'column',
                    'name' => $column->getName(),
                    'table' => $table,
                ];
            }
        }

        return $garbage;
    }

    /**
     * Anonymize a single database table using a single UPDATE query for each.
     */
    protected function anonymizeTableAtOnce(string $table, array $anonymizers): void
    {
        $this->output->write("- anonymizing...");

        $timer = $this->startTimer();
        $update = $this->createUpdateQuery($table);

        foreach ($anonymizers as $anonymizer) {
            \assert($anonymizer instanceof AbstractAnonymizer);
            $anonymizer->anonymize($update);
        }

        $update->executeStatement();

        $this->output->writeLine(\sprintf('[%s]', $this->formatTimer($timer)));
    }

    /**
     * Anonymize a single database table using one UPDATE query per target.
     */
    protected function anonymizeTablePerColumn(string $table, array $anonymizers): void
    {
        $total = \count($anonymizers);
        $count = 1;

        foreach ($anonymizers as $anonymizer) {
            \assert($anonymizer instanceof AbstractAnonymizer);

            $timer = $this->startTimer();

            $table = $anonymizer->getTableName();
            $target = $anonymizer->getColumnName();

            $this->output->write(\sprintf(
                '- anonymizing %d/%d: "%s"."%s"...', $count, $total, $table, $target
            ));

            $update = $this->createUpdateQuery($table);
            $anonymizer->anonymize($update);
            $update->executeStatement();
            $count++;

            $timer = $this->formatTimer($timer);
            $this->output->writeLine(\sprintf('[%s]', $timer));

            $this->getChainLogger()->info(
                'Target "{target}" from "{table}" table anonymized ({timer}).',
                [
                    'table' => $table,
                    'target' => $target,
                    'timer' => $timer,
                ]
            );
        }
    }

    protected function createUpdateQuery(string $table): Update
    {
        $builder = $this->getQueryBuilder();
        $update = $builder->update($table);
        $expr = $update->expression();

        // Add target table a second time into the FROM statement of the
        // UPDATE query, in order for anonymizers to be able to JOIN over
        // it. Otherwise, JOIN would not be possible for RDBMS that speak
        // standard SQL.
        if (Platform::SQLSERVER === $builder->getServerFlavor()) {
            // This is the only and single hack regarding the UPDATE clause
            // syntax, all RDBMS accept the following query:
            //
            //     UPDATE foo
            //     SET val = bar.val
            //     FROM foo AS foo_2
            //     JOIN bar ON bar.id = foo_2.id
            //     WHERE foo.id = foo_2.id
            //
            // Except for SQL Server, which cannot disambiguate the foo table
            // reference in the WHERE clause, so we have to write it this way:
            //
            //     UPDATE foo
            //     SET val = bar.val
            //     FROM (
            //         SELECT *
            //         FROM foo
            //     ) AS foo_2
            //     JOIN bar ON bar.id = foo_2.id
            //     WHERE foo.id = foo_2.id
            //
            // Which by the way also works with other RDBMS, but is an
            // optimization fence for some, because the nested SELECT becomes
            // a temporary table (especially for MySQL...). For those we need
            // to keep the original query, even if semantically identical.
            $update->join(
                $builder->select($table),
                $expr->where()->isEqual(
                    $expr->column(AbstractAnonymizer::JOIN_ID, $table),
                    $expr->column(AbstractAnonymizer::JOIN_ID, AbstractAnonymizer::JOIN_TABLE),
                ),
                AbstractAnonymizer::JOIN_TABLE
            );
        } elseif (Platform::SQLITE === $builder->getServerFlavor()) {
            // SQLite doesn't support DDL statements on tables, we cannot add
            // the join column with an int identifier. But, fortunately, it does
            // have a special ROWID column which is a unique int identifier for
            // each row we can use instead.
            // @see https://www.sqlite.org/lang_createtable.html#rowid
            $update->join(
                $builder
                    ->select($table)
                    ->column('*')
                    ->column('rowid', AbstractAnonymizer::JOIN_ID),
                $expr->where()->isEqual(
                    $expr->column("rowid", $table),
                    $expr->column(AbstractAnonymizer::JOIN_ID, AbstractAnonymizer::JOIN_TABLE),
                ),
                AbstractAnonymizer::JOIN_TABLE
            );
        } else {
            $update->join(
                $table,
                $expr->where()->isEqual(
                    $expr->column(AbstractAnonymizer::JOIN_ID, $table),
                    $expr->column(AbstractAnonymizer::JOIN_ID, AbstractAnonymizer::JOIN_TABLE),
                ),
                AbstractAnonymizer::JOIN_TABLE
            );
        }

        return $update;
    }

    protected function startTimer(): int|float
    {
        return \hrtime(true);
    }

    protected function formatTimer(null|int|float $timer): string
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
     * This method will add an anonymizer identifier as a serial column on the
     * table to anonymize: having a serial will allow UPDATE "target_table" FROM
     * "sample_table" statements to inject data randomly by joining over
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

        if ($platform instanceof SqlitePlatform) {
            // Do nothing, SQLite doesn't support DDL statements, you need to
            // recreate a new table with the new schema, then copy all data.
            // That's not what we want.
            // SQLite has a ROWID special column that does exactly what we need
            // i.e. having a unique int identifier for each row on which we can
            // join on.
            // @see https://www.sqlite.org/lang_createtable.html#rowid
            return;
        }

        if ($platform instanceof AbstractMySQLPlatform) {
            $this->addAnonymizerIdColumnMySql($table);

            return;
        }

        if ($platform instanceof SQLServerPlatform) {
            $this->addAnonymizerIdColumnSqlServer($table);

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
     * Remove anonymizer identifier column.
     */
    protected function removeAnonymizerIdColumn(string $table): void
    {
        foreach ($this->collectGarbageInto($table) as $item) {
            if ('column' === $item['type']) {
                $this->dropColumn($item['table'], $item['name']);
            }
            elseif ('index' === $item['type']) {
                $this->dropIndex($item['table'], $item['name']);
            }
            else {
                throw new \DomainException(\sprintf(
                    'Unsupported "%s" structure type.', $item['type']
                ));
            }
        }
    }

    /**
     * We are adding an arbitrary integer based identity column, MySQL only
     * supports this using AUTO_INCREMENT, and it can only exist one per table.
     *
     * Because we don't know anything about the target table, we can't add an
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
    protected function addAnonymizerIdColumnMySql(string $table): void
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

            $this->createNamedIndex(AbstractAnonymizer::JOIN_ID_INDEX, $table, AbstractAnonymizer::JOIN_ID);
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
     * Add second identity column for SQL Server.
     *
     * Pretty much like MySQL, SQL Server doesn't allow a second identity
     * column, we need to manually create a sequence. It's much easier than
     * MySQL thought.
     */
    protected function addAnonymizerIdColumnSqlServer(string $table): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $queryBuilder = $this->getQueryBuilder();

        $sequenceName = $platform->quoteIdentifier('_db_tools_seq_' . $table);

        $queryBuilder->executeStatement(
            <<<SQL
            ALTER TABLE ?::table DROP COLUMN IF EXISTS ?::column
            SQL,
            [
                $table,
                AbstractAnonymizer::JOIN_ID,
            ],
        );

        $queryBuilder->executeStatement(
            <<<SQL
            DROP SEQUENCE IF EXISTS ?::identifier;
            SQL,
            [$sequenceName],
        );

        $queryBuilder->executeStatement(
            <<<SQL
            CREATE SEQUENCE ?::identifier
                AS int
                START WITH 1
                INCREMENT BY 1;
            SQL,
            [$sequenceName],
        );

        $queryBuilder->executeStatement(
            <<<SQL
            ALTER TABLE ?::table
                ADD ?::column int NOT NULL DEFAULT (
                    NEXT VALUE FOR ?::identifier
                );
            SQL,
            [
                $table,
                AbstractAnonymizer::JOIN_ID,
                $sequenceName,
            ],
        );

        // Remove default value, default values are constraints in SQL Server
        // we must find its auto generated identifier. Removing the constraint
        // at this stade is mandatory otherwise column will not be deletable
        // later when anonymizator will proceed to cleanup.
        $this->dropColumnConstraintsSqlServer($table, AbstractAnonymizer::JOIN_ID);

        $queryBuilder->executeStatement(
            <<<SQL
            DROP SEQUENCE IF EXISTS ?::identifier;
            SQL,
            [$sequenceName],
        );

        $this->createNamedIndex(AbstractAnonymizer::JOIN_ID_INDEX, $table, AbstractAnonymizer::JOIN_ID);
    }

    /**
     * Create an index with given name.
     */
    protected function createNamedIndex(string $indexName, string $table, string...$column): void
    {
        $columnList = \implode(', ', \array_map(fn ($value) => '?::column', $column));

        $this
            ->getQueryBuilder()
            ->executeStatement(
                <<<SQL
                CREATE INDEX ?::identifier ON ?::table ({$columnList})
                SQL,
                [$indexName, $table, ...$column]
            )
        ;
    }

    /**
     * Create an index.
     */
    protected function createIndex(string $table, string...$column): void
    {
        $indexName = $table . '_' . \implode('_', $column) . '_idx';

        $this->createNamedIndex($indexName, $table, ...$column);
    }

    /**
     * Drop an index.
     */
    protected function dropIndex(string $table, string $indexName): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform || $platform instanceof SQLServerPlatform) {
            $this
                ->getQueryBuilder()
                ->executeStatement(
                    <<<SQL
                    DROP INDEX ?::identifier ON ?::table
                    SQL,
                    [$indexName, $table]
                )
            ;

            return;
        }

        $this
            ->getQueryBuilder()
            ->executeStatement(
                <<<SQL
                DROP INDEX ?::identifier
                SQL,
                [$indexName]
            )
        ;
    }

    /**
     * Drop a single table column.
     */
    protected function dropColumn(string $tableName, string $columnName): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $platform = $this->connection->getDatabasePlatform();

        foreach ($schemaManager->listTableColumns($tableName) as $column) {
            \assert($column instanceof Column);

            if ($column->getName() === $columnName) {

                if ($platform instanceof SQLServerPlatform) {
                    // SQL server requires that you drop constraints on column
                    // prior to deleting the column. So let's do that.
                    $this->dropColumnConstraintsSqlServer($tableName, $columnName);
                }

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

    /**
     * Drop all columns constraints on SQL Server.
     */
    protected function dropColumnConstraintsSqlServer(string $tableName, string $columnName): void
    {
        // @see https://stackoverflow.com/questions/1364526/how-do-you-drop-a-default-value-from-a-column-in-a-table
        $this->getQueryBuilder()->executeStatement(
            <<<SQL
            DECLARE @ConstraintName nvarchar(200)
            SELECT @ConstraintName = Name
                FROM SYS.DEFAULT_CONSTRAINTS
                WHERE PARENT_OBJECT_ID = OBJECT_ID(?)
                    AND PARENT_COLUMN_ID = (
                        SELECT column_id
                            FROM sys.columns
                            WHERE NAME = ?
                                AND object_id = OBJECT_ID(?)
                    )
            IF @ConstraintName IS NOT NULL
            EXEC('ALTER TABLE ' + ? + ' DROP CONSTRAINT ' + @ConstraintName)
            SQL,
            [
                $tableName,
                $columnName,
                $tableName,
                $tableName,
            ],
        );
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
