<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Expression;
use MakinaCorpus\QueryBuilder\ExpressionFactory;
use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;

abstract class AbstractAnonymizer
{
    public const JOIN_ID = '_db_tools_id';
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

    protected function getSetIfNotNullExpression(mixed $valueExpression, mixed $columnExpression = null): Expression
    {
        if (null === $columnExpression) {
            $columnExpression = ExpressionFactory::column($this->columnName, $this->tableName);
        }
        return ExpressionFactory::ifThen(ExpressionFactory::where()->isNotNull($columnExpression), $valueExpression);
    }

    /**
     * Generate an SQL text pad left expression.
     */
    protected function getSqlTextPadLeftExpression(mixed $textExpression, int $padSize, string $rawPadString): Expression
    {
        return ExpressionFactory::raw(
            'lpad(?, ?, ?)',
            [
                ExpressionFactory::cast($textExpression, 'text'),
                $padSize,
                $rawPadString,
            ],
        );
    }
}
