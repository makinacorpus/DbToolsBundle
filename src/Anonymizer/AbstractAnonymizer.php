<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

abstract class AbstractAnonymizer
{
    public const TEMP_TABLE_PREFIX = 'anonymizer_sample_';

    final public function __construct(
        protected string $tableName,
        protected string $columnName,
        protected Connection $connection,
        protected Options $options,
    ) {}

    public static function id(): string
    {
        return self::getMetadata()->id();
    }

    public static function getMetadata(): AsAnonymizer
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
    public function getColumnName(): string
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
    abstract public function anonymize(QueryBuilder $updateQuery): void;

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
    protected function createSampleTempTable(array $columns, array $values = [], ?string $tableName = null, array $types = []): string
    {
        $types = \array_values($types);
        $columns = \array_values($columns);
        $columnCount = \count($columns);

        if (!$tableName) {
            $tableName = $this->generateTempTableName();
        }

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

    protected function getSetIfNotNullExpression(string $columnExpression, string $valueExpression): string
    {
        return \sprintf('case when %s is not null then %s end', $columnExpression, $valueExpression);
    }

    protected function getStringAggExpression(string $textExpression, string $rawJoinString = ','): string
    {
        $plateform = $this->connection->getDatabasePlatform();
        $quotedJoinString = $plateform->quoteStringLiteral($rawJoinString);

        return match (true) {
            $plateform instanceof MySQLPlatform => \sprintf("group_concat(%s, %s)", $textExpression, $quotedJoinString),
            // We are going to add a forced CAST here so that the user may
            // give anything, an int, a date, etc... MySQL doesn't need that
            // because it uses type coercition and does the job implicitely.
            default => \sprintf("string_agg(cast(%s as text), %s)", $textExpression, $quotedJoinString),
        };
    }

    /**
     * Generate an SQL text pad left expression.
     */
    protected function getSqlTextPadLeftExpression(string $textExpression, int $padSize, string $rawPadString): string
    {
        $plateform = $this->connection->getDatabasePlatform();
        $quotedPadString = $plateform->quoteStringLiteral($rawPadString);

        return match (true) {
            $plateform instanceof MySQLPlatform => \sprintf("lpad(%s, %d, %s)", $textExpression, $padSize, $quotedPadString),
            // We are going to add a forced CAST here so that the user may
            // give anything, an int, a date, etc... MySQL doesn't need that
            // because it uses type coercition and does the job implicitely.
            default => \sprintf("lpad(cast(%s as text), %d, %s)", $textExpression, $padSize, $quotedPadString),
        };
    }

    /**
     * Generate an SQL expression that creates a random integer between 0
     * and the given maximum.
     */
    protected function getSqlRandomIntExpression(int $max, int $min = 0): string
    {
        return \sprintf("cast(%s * (%d - %d + 1) as int)", $this->getSqlRandomExpression(), $max, $min);
    }

    /**
     * Generate a decimal number between 0 and 1.
     */
    protected function getSqlRandomExpression(): string
    {
        $plateform = $this->connection->getDatabasePlatform();

        return match (true) {
            $plateform instanceof MySQLPlatform => "rand()",
            $plateform instanceof PostgreSQLPlatform => "random()",
            // There is no SQL standard for this as we know of.
            default => throw new \InvalidArgumentException(\sprintf('%s is not supported.', \get_class($plateform)))
        };
    }
}
