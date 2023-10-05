<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target\Target;

abstract class AbstractAnonymizer
{
    public function __construct(
        protected Connection $connection,
    ) { }

    /**
     * Initialize your anonymizer
     *
     * Override this method for your needs, for example create a temporary
     * table with dummy data.
     *
     * This method is launch once at the beginning of the anonymization process.
     */
    public function initialize(): self
    {
        return $this;
    }

    /**
     * Add statement to existing update query to anonymize a specific target.
     */
    abstract public function anonymize(QueryBuilder $updateQuery, Target $target, Options $options): self;

    /**
     * Clean your anonymizer
     *
     * Override this method for your needs, for example drop a temporary
     * table created in initialiaze() methode.
     *
     * This method is only launch once at the end of the anonymization process.
     */
    public function clean(): self
    {
        return $this;
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
                $type = Type::getType('string');
            } else if (!$type instanceof Type) {
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
        return \uniqid('anonymizer_sample_');
    }

    protected function getSqlRandomExpression(): string
    {
        $plateform = $this->connection->getDatabasePlatform();

        return match (true) {
            $plateform instanceof MySQLPlatform => "rand()",
            $plateform instanceof PostgreSQLPlatform => "random()",
            default => throw new \InvalidArgumentException(\sprintf('%s is not supported.', \get_class($plateform)))
        };
    }
}