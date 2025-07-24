<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\Vendor;

abstract class AbstractAnonymizer
{
    public const JOIN_ID = '_db_tools_id';
    public const JOIN_ID_INDEX = 'target_table_db_tools_id_idx';
    public const JOIN_TABLE = '_target_table';
    public const TEMP_TABLE_PREFIX = '_db_tools_sample_';

    final public function __construct(
        protected string $tableName,
        protected string $columnName,
        protected DatabaseSession $databaseSession,
        protected readonly Context $context,
        protected readonly Options $options,
    ) {
        $this->validateOptions();
    }

    /**
     * Get table name.
     *
     * @internal
     *   For reporting while anonymizing.
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Get column name.
     *
     * It can either return a real colum name, for column-level anonymizers,
     * but it may also return an arbitrary name, for table-level anonymizers.
     *
     * @internal
     *   For reporting while anonymizing.
     */
    final public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * Get join column name.
     *
     * @internal
     *   Public for unit tests only, otherwise protected.
     */
    final public function getJoinId(): string
    {
        return self::JOIN_ID;
    }

    /**
     * Get join column expression.
     *
     * @internal
     *   Public for unit tests only, otherwise protected.
     */
    protected function getJoinColumn(): Expression
    {
        return ExpressionFactory::column($this->getJoinId(), self::JOIN_TABLE);
    }

    /**
     * Validate options given to the Anonymizer.
     *
     * Override this method for your needs, for example validate that
     * an option `foo` is given with the correct type.
     *
     * This method is launched before the beginning of the anonymization process.
     *
     * If you override this method, you must call `parent::validateOptions()`
     * at the beginning of your implementation.
     *
     * @throws \Exception if any option is invalid.
     */
    protected function validateOptions(): void
    {
        if ($this->hasSampleSizeOption()) {
            if ($this->options->has('sample_size')) {
                $value = $this->options->getInt('sample_size');
                if ($value <= 0) {
                    throw new \InvalidArgumentException("'sample_size' option must be a positive integer.");
                }
            }
        }
    }

    /**
     * Does this anonymizer has a "sample size" option.
     */
    protected function hasSampleSizeOption(): bool
    {
        return false;
    }

    /**
     * Default sample size, goes along the "sample size" option set to true.
     */
    protected function getDefaultSampleSize(): int
    {
        return 500;
    }

    /**
     * Default sample size, goes along the "sample size" option set to true.
     */
    protected function getSampleSize(): int
    {
        return $this->options->getInt('sample_size', $this->getDefaultSampleSize());
    }

    /**
     * Initialize your anonymizer.
     *
     * Override this method for your needs, for example create a temporary
     * table with dummy data.
     *
     * This method is launch once at the beginning of the anonymization process.
     */
    public function initialize(): void {}

    /**
     * Add statement to existing update query to anonymize a specific target.
     */
    abstract public function anonymize(Update $update): void;

    /**
     * Clean your anonymizer
     *
     * Override this method for your needs, for example drop a temporary
     * table created in initialiaze() methode.
     *
     * This method is only launch once at the end of the anonymization process.
     */
    public function clean(): void {}

    /**
     * Count table.
     */
    protected function countTable(string $table): int
    {
        return (int) $this
            ->databaseSession
            ->select($table)
            ->columnRaw('count(*)')
            ->executeQuery()
            ->fetchOne()
        ;
    }

    /**
     * Create a temporary table with one or more sample columns, and populate it
     * using the given values.
     *
     * @param string[] $columns
     *   Column names. Type will be text.
     * @param string[][] $values
     *   Each value must have the same number of values than the column count.
     *
     * @return string
     *   The table name.
     */
    protected function createSampleTempTable(array $columns, array $values = [], array $types = []): string
    {
        $types = \array_values($types);
        $columns = \array_values($columns);
        $columnCount = \count($columns);
        $tableName = $this->generateTempTableName();

        $transaction = $this
            ->databaseSession
            ->getSchemaManager()
            ->modify()
            ->createTable($tableName)
        ;

        foreach ($columns as $index => $name) {
            $transaction->column($name, $types[$index] ?? 'text', false);
        }

        $transaction->endTable()->commit();

        $insert = $this
            ->databaseSession
            ->insert($tableName)
            ->columns($columns)
        ;

        // SQL Server supports a maximum of 2100 parameters per query.
        // This limit probably also exists with other RDBMSs, to avoid
        // errors, we insert rows each 2000 parameters.
        $parametersCount = 0;
        foreach ($values as $key => $value) {
            // Allow single raw value when there is only one column.
            $value = (array) $value;
            $parametersCount += \count($value);

            if ($parametersCount >= 2000) {
                $insert->executeStatement();

                $insert = $this
                    ->databaseSession
                    ->insert($tableName)
                    ->columns($columns)
                ;
                $parametersCount = 0;
            }

            if ($columnCount !== \count($value)) {
                throw new \InvalidArgumentException(\sprintf(
                    "Row %s in sample list column count (%d) mismatch with table column count (%d)",
                    $key,
                    \count($values),
                    $columnCount
                ));
            }
            $insert->values($value);
        }

        $insert->executeStatement();

        return $tableName;
    }

    protected function generateTempTableName(): string
    {
        return \uniqid(self::TEMP_TABLE_PREFIX);
    }

    /**
     * Return a random float between 0 and 1 expression.
     *
     * makina-corpus/query-builder already support this, but due to an odd SQL
     * Server non standard behaviour, we reimplement it here: SQL Server RAND()
     * return value will always be the same no matter how many time you call it
     * inside a single SQL query. This means that when you update many rows
     * using a RAND() based value, all rows will have the same value.
     *
     * We are going to get arround by injected a random value as seed for the
     * RAND() function, because SQL Server allows this. Using NEWID() which
     * generates an GUID might be slow, but we'll that at usage later.
     *
     * This function takes care of this, and will return an expression that
     * works with all supported RDBMS.
     */
    protected function getRandomExpression(): Expression
    {
        if ($this->databaseSession->vendorIs(Vendor::SQLSERVER)) {
            return ExpressionFactory::raw('rand(abs(checksum(newid())))');
        }
        return ExpressionFactory::random();
    }

    /**
     * For the same reason as getRandomExpression().
     */
    protected function getRandomIntExpression(int $max, int $min = 0): Expression
    {
        if ($this->databaseSession->vendorIs(Vendor::SQLSERVER)) {
            return ExpressionFactory::raw(
                'FLOOR(? * (? - ? + 1) + ?)',
                [$this->getRandomExpression(), ExpressionFactory::cast($max, 'int'), $min, $min]
            );
        }
        return ExpressionFactory::randomInt($max, $min);
    }

    protected function getSetIfNotNullExpression(mixed $valueExpression, mixed $columnExpression = null): Expression
    {
        if (null === $columnExpression) {
            $columnExpression = ExpressionFactory::column($this->columnName, $this->tableName);
        }
        return ExpressionFactory::ifThen(ExpressionFactory::where()->isNotNull($columnExpression), $valueExpression);
    }
}
