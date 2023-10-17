<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class of anonymizers that work on a Table target, and allow updating
 * more than one column at a time.
 */
abstract class AbstractMultipleColumnAnonymizer extends AbstractTableAnonymizer
{
    private ?string $sampleTableName = null;

    /**
     * Get column names.
     *
     * @return string[]
     */
    abstract protected function getColumnNames(): array;

    /**
     * Get samples.
     *
     * @return array<string[]>
     *   Each value must have the exact same number of values that the column count.
     */
    abstract protected function getSamples(): array;

    /**
     * Get column types.
     *
     * If you don't override this method, all columns will have the SQL "text"
     * type per default.
     *
     * @return string[]
     *   Each value is a type name that Doctrine/DBAL knows.
     *   The array must contain the same value count as column names.
     */
    protected function getColumnTypes(): array
    {
        return [];
    }

    /**
     * Get sample table name, if null one will be automatically created.
     */
    protected function getSampleTableName(): ?string
    {
        return $this->sampleTableName ?? ($this->sampleTableName = $this->generateTempTableName());
    }

    /**
     * @inheritdoc
     */
    public function initialize(): void
    {
        $this->createSampleTempTable(
            $this->getColumnNames(),
            $this->getSamples(),
            $this->getSampleTableName(),
            $this->getColumnTypes(),
        );
    }

    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $query): void
    {
        $columns = $this->getColumnNames();
        $sampleTableName = $this->getSampleTableName();

        if (0 >= $this->options->count()) {
            throw new \InvalidArgumentException(\sprintf(
                "Options are empty. You should at least give one of those: %s",
                \implode(', ', $columns)
            ));
        }

        $plateform = $this->connection->getDatabasePlatform();
        $columnOptions = \array_filter($columns, fn ($column) => $this->options->has($column));

        $random = $this->connection
            ->createQueryBuilder()
            ->select(...\array_map(fn ($column) => $sampleTableName . '.' . $column, $columnOptions))
            ->from($sampleTableName)
            ->setMaxResults(1)
            ->where(
                $this->connection->createExpressionBuilder()->notLike(
                    $plateform->quoteIdentifier($this->tableName) . '.' . $this->options->get(\reset($columnOptions)),
                    $sampleTableName . '.' . \reset($columnOptions)
                )
            )
            ->orderBy($this->getSqlRandomExpression())
        ;

        $query->set(
            \sprintf(
                '(%s)',
                \implode(
                    ', ',
                    \array_map(
                        fn ($column) => $plateform->quoteIdentifier($this->options->get($column)),
                        $columnOptions
                    )
                )
            ),
            \sprintf('(%s)', $random),
        );
    }

    /**
     * @inheritdoc
     */
    public function clean(): void
    {
        $this->connection->createSchemaManager()->dropTable($this->getSampleTableName());
    }

}
