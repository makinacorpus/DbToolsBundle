<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizator;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Query\Update;

abstract class AbstractAnonymizer
{
    public const JOIN_ID = '_db_tools_id';
    public const JOIN_ID_INDEX = 'target_table_db_tools_id_idx';
    public const JOIN_TABLE = '_target_table';
    public const TEMP_TABLE_PREFIX = '_db_tools_sample_';

    final public function __construct(
        protected string $tableName,
        protected string $columnName,
        protected Connection $connection,
        protected Options $options,
    ) {}

    final public static function id(): string
    {
        return self::getMetadata()->id();
    }

    final public static function getMetadata(): AsAnonymizer
    {
        if ($attributes = (new \ReflectionClass(static::class))->getAttributes(AsAnonymizer::class)) {
            return $attributes[0]->newInstance();
        }

        throw new \LogicException("Each anonymizer should add a AsAnonymizer attribute.");
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
     * Get a random, global salt for anonymizing hashed values.
     */
    protected function getSalt(): string
    {
        return $this->options->get('salt') ?? Anonymizator::generateRandomSalt();
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
    public function validateOptions(): void {}

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
        return (int) (new DoctrineQueryBuilder($this->connection))
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

        $tableColumns = [];
        foreach ($columns as $index => $name) {
            $type = $types[$index] ?? null;
            if (!$type) {
                $type = Type::getType('text');
            } elseif (!$type instanceof Type) {
                $type = Type::getType($type);
            }
            $tableColumns[] = new Column($name, $type);
        }

        $this->connection
            ->createSchemaManager()
            ->createTable(new Table($tableName, $tableColumns))
        ;

        $this->connection->beginTransaction();
        try {
            foreach ($values as $key => $value) {
                // Allow single raw value when there is only one column.
                $value = (array) $value;

                if ($columnCount !== \count($value)) {
                    throw new \InvalidArgumentException(\sprintf("Row %s in sample list column count (%d) mismatch with table column count (%d)", $key, \count($values), $columnCount));
                }

                $this->connection
                    ->createQueryBuilder()
                    ->insert($tableName)
                    ->values(\array_combine(
                        $columns,
                        \array_map(fn ($column) => ':'. $column, $columns)
                    ))
                    ->setParameters(\array_combine($columns, $value))
                    ->executeQuery()
                ;
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }

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
        if ($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
            return ExpressionFactory::raw('rand(abs(checksum(newid())))');
        }
        return ExpressionFactory::random();
    }

    /**
     * For the same reason as getRandomExpression().
     */
    protected function getRandomIntExpression(int $max, int $min = 0): Expression
    {
        if ($this->connection->getDatabasePlatform() instanceof SQLServerPlatform) {
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
