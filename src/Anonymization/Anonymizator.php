<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Context;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Helper\Format;
use MakinaCorpus\DbToolsBundle\Helper\Output\NullOutput;
use MakinaCorpus\DbToolsBundle\Helper\Output\OutputInterface;
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Vendor;
use MakinaCorpus\QueryBuilder\Error\Server\DatabaseObjectDoesNotExistError;
use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\Schema\Read\Column;
use MakinaCorpus\QueryBuilder\Schema\Read\Index;
use MakinaCorpus\QueryBuilder\Type\Type;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Anonymizator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Used for the garbage collection/cleanup procedure, removes tables with older names.
     */
    protected const DEPRECATED_JOIN_ID = [
        '_anonymize_id', // @todo Remove in 3.0
        '_anonymizer_id', // @todo Remove in 3.0
    ];

    /**
     * Used for the garbage collection/cleanup procedure, removes tables with older names.
     */
    protected const DEPRECATED_TEMP_TABLE_PREFIX = [
        'anonymizer_sample_', // @todo Remove in 3.0
    ];

    private OutputInterface $output;
    private readonly Context $defaultContext;

    public function __construct(
        private DatabaseSession $databaseSession,
        private AnonymizerRegistry $anonymizerRegistry,
        private AnonymizationConfig $anonymizationConfig,
        ?Context $defaultContext = null,
    ) {
        $this->logger = new NullLogger();
        $this->output = new NullOutput();
        $this->defaultContext = $defaultContext ?? new Context();
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

    #[\Deprecated(message: "Will be removed in 3.0, use Context::generateRandomSalt() instead.", since: "2.1.0")]
    public static function generateRandomSalt(): string
    {
        return Context::generateRandomSalt();
    }

    /**
     * Create anonymizer instance.
     */
    protected function createAnonymizer(AnonymizerConfig $config, Context $context): AbstractAnonymizer
    {
        return $this->anonymizerRegistry->createAnonymizer(
            $config->anonymizer,
            $config,
            $context,
            $this->databaseSession
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
     *
     * @throws \Exception if anonymization config is invalid.
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
        $context = clone $this->defaultContext;

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
        $anonymizers = [];

        // First of all, we create each anonymizer for all tables
        // and validate their configuration.
        foreach ($plan as $table => $targets) {
            $anonymizers[$table] = [];
            foreach ($this->anonymizationConfig->getTableConfig($table, $targets) as $target => $config) {
                $anonymizers[$table][] = $this->createAnonymizer($config, $context);
            }
        }

        // Then, we initialize them and we run the anonymization.
        foreach ($plan as $table => $targets) {
            // Base context for logging.
            $context = [
                'table' => $table,
                'targets' => \implode('", "', $targets),
            ];

            $initTimer = $this->startTimer();

            try {
                $this->output->writeLine(
                    ' * table %d/%d: "%s" ("%s")',
                    $count,
                    $total,
                    $table,
                    \implode('", "', $targets)
                );

                $this->output->indent();
                $this->output->write("- initializing anonymizers...");

                \array_walk($anonymizers[$table], fn (AbstractAnonymizer $anonymizer) => $anonymizer->initialize());

                $this->addAnonymizerIdColumn($table);

                $timer = $this->formatTimer($initTimer);
                $this->output->writeLine('[%s]', $timer);

                if ($atOnce) {
                    $this->anonymizeTableAtOnce($table, $anonymizers[$table]);
                    $this->logger->info(
                        'Table "{table}" anonymized. Targets were: "{targets}" ({timer}).',
                        $context + ['timer' => $timer]
                    );
                } else {
                    $this->anonymizeTablePerColumn($table, $anonymizers[$table]);
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Exception caught when anonymizing "{table}" table: {error}. (Targets were: "{targets}").',
                    $context + ['error' => $e->getMessage()]
                );

                throw $e;
            } finally {
                $cleanTimer = $this->startTimer();
                // Clean up everything, even in case of any error.
                $this->output->write("- cleaning anonymizers...");
                \array_walk($anonymizers[$table], fn (AbstractAnonymizer $anonymizer) => $anonymizer->clean());

                $this->removeAnonymizerIdColumn($table);

                $cleanTimer = $this->formatTimer($cleanTimer);
                $this->output->writeLine('[%s]', $cleanTimer);
                $this->output->writeLine("- total " . $this->formatTimer($initTimer));
                $this->output->outdent();

                $this->logger->info(
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
        $transaction = $this->databaseSession->getSchemaManager()->modify();

        foreach ($this->collectGarbage() as $item) {
            switch ($item['type']) {
                case 'table':
                    $this->output->writeLine(
                        "Dropping table: %s",
                        $item['name']
                    );
                    $transaction->dropTable($item['name']);
                    break;

                case 'column':
                    $this->output->writeLine(
                        "Dropping column: %s.%s",
                        $item['table'],
                        $item['name']
                    );
                    $transaction->dropColumn($item['table'], $item['name']);
                    break;

                case 'index':
                    $this->output->writeLine(
                        "Dropping index: %s.%s",
                        $item['table'],
                        $item['name']
                    );
                    $transaction->dropIndex($item['table'], $item['name']);
                    break;

                default:
                    throw new \DomainException(\sprintf(
                        'Unsupported "%s" structure type.',
                        $item['type']
                    ));
            }
        }

        $transaction->commit();
    }

    /**
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
        $schemaManager = $this->databaseSession->getSchemaManager();
        $garbage = [];

        $prefixes = [AbstractAnonymizer::TEMP_TABLE_PREFIX];
        // For backward compatibilty, removes legacy table names as well.
        foreach (self::DEPRECATED_TEMP_TABLE_PREFIX as $prefix) {
            $prefixes[] = $prefix;
        }

        foreach ($schemaManager->listTables() as $tableName) {
            $found = false;
            foreach ($prefixes as $prefix) {
                if (\str_starts_with($tableName, $prefix)) {
                    $garbage[] = ['type' => 'table', 'name' => $tableName];
                    $found = true;
                    break;
                }
            }

            // If table is not marked for deletion, lookup for added columns.
            if (!$found) {
                $garbage = \array_merge($garbage, $this->collectGarbageInto($tableName));
            }
        }

        return $garbage;
    }

    protected function collectGarbageInto(string $table): array
    {
        $schemaManager = $this->databaseSession->getSchemaManager();
        $garbage = [];

        try {
            $table = $schemaManager->getTable(name: $table);
        } catch (DatabaseObjectDoesNotExistError) {
            // @todo Log error?
            return [];
        }

        // List indexes to remove before columns to avoid problems with
        // SQL Server which doesn't support to drop a column still used
        // by an index.
        foreach ($table->getIndexes() as $index) {
            \assert($index instanceof Index);

            if (AbstractAnonymizer::JOIN_ID_INDEX === $index->getName()) {
                $garbage[] = [
                    'type' => 'index',
                    'name' => $index->getName(),
                    'table' => $table->getName(),
                ];
            }
        }

        $names = [AbstractAnonymizer::JOIN_ID];
        // For backward compatibilty, removes legacy column names as well.
        foreach (self::DEPRECATED_JOIN_ID as $name) {
            $names[] = $name;
        }

        foreach ($table->getColumns() as $column) {
            \assert($column instanceof Column);

            foreach ($names as $name) {
                if ($name === $column->getName()) {
                    $garbage[] = [
                        'type' => 'column',
                        'name' => $column->getName(),
                        'table' => $table->getName(),
                    ];
                    break;
                }
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

        $this->output->writeLine('[%s]', $this->formatTimer($timer));
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

            $this->output->write(
                '- anonymizing %d/%d: "%s"."%s"...',
                $count,
                $total,
                $table,
                $target
            );

            $update = $this->createUpdateQuery($table);
            $anonymizer->anonymize($update);
            $update->executeStatement();
            $count++;

            $timer = $this->formatTimer($timer);
            $this->output->writeLine('[%s]', $timer);

            $this->logger->info(
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
        $update = $this->databaseSession->update($table);
        $expr = $update->expression();

        // Add target table a second time into the FROM statement of the
        // UPDATE query, in order for anonymizers to be able to JOIN over
        // it. Otherwise, JOIN would not be possible for RDBMS that speak
        // standard SQL.
        if ($this->databaseSession->vendorIs(Vendor::SQLSERVER)) {
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
                $this->databaseSession->select($table),
                $expr->where()->isEqual(
                    $expr->column(AbstractAnonymizer::JOIN_ID, $table),
                    $expr->column(AbstractAnonymizer::JOIN_ID, AbstractAnonymizer::JOIN_TABLE),
                ),
                AbstractAnonymizer::JOIN_TABLE
            );
        } elseif ($this->databaseSession->vendorIs(Vendor::SQLITE)) {
            // SQLite doesn't support DDL statements on tables, we cannot add
            // the join column with an int identifier. But, fortunately, it does
            // have a special ROWID column which is a unique int identifier for
            // each row we can use instead.
            // @see https://www.sqlite.org/lang_createtable.html#rowid
            $update->join(
                $this
                    ->databaseSession
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
        $schemaManager = $this->databaseSession->getSchemaManager();

        foreach ($schemaManager->getTable($table)->getColumns() as $column) {
            \assert($column instanceof Column);

            if (AbstractAnonymizer::JOIN_ID === $column->getName()) {
                return;
            }
        }

        if ($this->databaseSession->vendorIs(Vendor::SQLITE)) {
            // Do nothing, SQLite doesn't support DDL statements, you need to
            // recreate a new table with the new schema, then copy all data.
            // That's not what we want.
            // SQLite has a ROWID special column that does exactly what we need
            // i.e. having a unique int identifier for each row on which we can
            // join on.
            // @see https://www.sqlite.org/lang_createtable.html#rowid
            return;
        }

        if ($this->databaseSession->vendorIs([Vendor::MARIADB, Vendor::MYSQL])) {
            $this->addAnonymizerIdColumnMySql($table);

            return;
        }

        if ($this->databaseSession->vendorIs(Vendor::SQLSERVER)) {
            $this->addAnonymizerIdColumnSqlServer($table);

            return;
        }

        $schemaManager
            ->modify()
            ->addColumn(
                name: AbstractAnonymizer::JOIN_ID,
                nullable: true,
                table: $table,
                type: Type::identityBig(),
            )
            ->commit()
        ;
    }

    /**
     * Remove anonymizer identifier column.
     */
    protected function removeAnonymizerIdColumn(string $table): void
    {
        foreach ($this->collectGarbageInto($table) as $item) {
            if ('column' === $item['type']) {
                $this->dropColumn($item['table'], $item['name']);
            } elseif ('index' === $item['type']) {
                $this->dropIndex($item['table'], $item['name']);
            } else {
                throw new \DomainException(\sprintf(
                    'Unsupported "%s" structure type.',
                    $item['type']
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
        $this
            ->databaseSession
            ->getSchemaManager()
            ->modify()
            ->addColumn($table, AbstractAnonymizer::JOIN_ID, Type::intBig(), true)
            ->commit()
        ;

        $sequenceTableName = '_db_tools_seq_' . $table;
        $functionName = '_db_tools_seq_' . $table . '_get';

        $this->databaseSession->executeStatement(
            <<<SQL
            DROP TABLE IF EXISTS ?::table
            SQL,
            [$sequenceTableName],
        );

        $this->databaseSession->executeStatement(
            <<<SQL
            DROP FUNCTION IF EXISTS ?::id
            SQL,
            [$functionName],
        );

        $this->databaseSession->executeStatement(
            <<<SQL
            CREATE TABLE ?::table (
                `value` BIGINT DEFAULT NULL
            );
            SQL,
            [$sequenceTableName],
        );

        $this->databaseSession->executeStatement(
            <<<SQL
            INSERT INTO ?::table (`value`) VALUES (0);
            SQL,
            [$sequenceTableName],
        );

        try {
            if ($this->databaseSession->vendorVersionIs('8.0', '<')) {
                $this->databaseSession->executeStatement(
                    <<<SQL
                    CREATE FUNCTION ?::id() RETURNS INTEGER
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
            } else {
                $this->databaseSession->executeStatement(
                    <<<SQL
                    CREATE FUNCTION ?::id() RETURNS BIGINT
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
            }

            $this->databaseSession->executeStatement(
                <<<SQL
                UPDATE ?::table SET ?::column = ?::id();
                SQL,
                [
                    $table,
                    AbstractAnonymizer::JOIN_ID,
                    $functionName,
                ],
            );

            $this->createNamedIndex(AbstractAnonymizer::JOIN_ID_INDEX, $table, AbstractAnonymizer::JOIN_ID);
        } finally {
            $this->databaseSession->executeStatement(
                <<<SQL
                DROP FUNCTION IF EXISTS ?::id;
                SQL,
                [$functionName],
            );

            $this->databaseSession->executeStatement(
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
        $sequenceName = '_db_tools_seq_' . $table;

        $this->databaseSession->executeStatement(
            <<<SQL
            ALTER TABLE ?::table DROP COLUMN IF EXISTS ?::column
            SQL,
            [
                $table,
                AbstractAnonymizer::JOIN_ID,
            ],
        );

        $this->databaseSession->executeStatement(
            <<<SQL
            DROP SEQUENCE IF EXISTS ?::id;
            SQL,
            [$sequenceName],
        );

        $this->databaseSession->executeStatement(
            <<<SQL
            CREATE SEQUENCE ?::id
                AS int
                START WITH 1
                INCREMENT BY 1;
            SQL,
            [$sequenceName],
        );

        $this->databaseSession->executeStatement(
            <<<SQL
            ALTER TABLE ?::table
                ADD ?::column int NOT NULL DEFAULT (
                    NEXT VALUE FOR ?::id
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

        $this->databaseSession->executeStatement(
            <<<SQL
            DROP SEQUENCE IF EXISTS ?::id;
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

        $this->databaseSession->executeStatement(
            <<<SQL
            CREATE INDEX ?::id ON ?::table ({$columnList})
            SQL,
            [$indexName, $table, ...$column]
        );
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
        $this
            ->databaseSession
            ->getSchemaManager()
            ->modify()
            ->dropIndex($table, $indexName)
            ->commit()
        ;
    }

    /**
     * Drop a single table column.
     */
    protected function dropColumn(string $tableName, string $columnName): void
    {
        $schemaManager = $this->databaseSession->getSchemaManager();

        foreach ($schemaManager->getTable($tableName)->getColumns() as $column) {
            \assert($column instanceof Column);

            if ($column->getName() === $columnName) {
                if ($this->databaseSession->vendorIs(Vendor::SQLSERVER)) {
                    // SQL server requires that you drop constraints on column
                    // prior to deleting the column. So let's do that.
                    $this->dropColumnConstraintsSqlServer($tableName, $columnName);
                }

                $schemaManager
                    ->modify()
                        ->dropColumn(
                            table: $tableName,
                            name: $columnName
                        )
                    ->commit()
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
        $this->databaseSession->executeStatement(
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

    /**
     * @return array<string, array<string, string>> errors indexed by table and target.
     */
    public function checkAnonymizationConfig(): array
    {
        $errors = [];
        foreach ($this->anonymizationConfig->all() as $table => $tableConfig) {
            foreach ($tableConfig as $config) {
                try {
                    $this->createAnonymizer($config, $this->defaultContext);
                } catch (\Exception $e) {
                    if (!\key_exists($table, $errors)) {
                        $errors[$table] = [];
                    }

                    $errors[$table][$config->targetName] = $e->getMessage();
                }
            }
        }

        return $errors;
    }
}
