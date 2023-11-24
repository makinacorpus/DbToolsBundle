<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;

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
     * @inheritdoc
     */
    public function initialize(): void
    {
        $this->sampleTableName = $this->createSampleTempTable(
            $this->getColumnNames(),
            $this->getSamples(),
            $this->getColumnTypes(),
        );
    }

    /**
     * @inheritdoc
     */
    public function anonymize(Update $update): void
    {
        $columns = $this->getColumnNames();

        if (0 >= $this->options->count()) {
            throw new \InvalidArgumentException(\sprintf(
                "Options are empty. You should at least give one of those: %s",
                \implode(', ', $columns)
            ));
        }

        $columnOptions = \array_filter($columns, fn ($column) => $this->options->has($column));

        $expr = $update->expression();

        $targetCount = $this->countTable($this->tableName);
        $sampleCount = $this->countTable($this->sampleTableName);

        $joinAlias = $this->sampleTableName . '_' . $this->columnName;
        $join = (new Select($this->sampleTableName))
            ->columns($columnOptions)
            ->columnRaw('ROW_NUMBER() OVER (ORDER BY ?)', 'rownum', [$expr->random()])
            ->range($targetCount) // Avoid duplicate rows.
        ;

        $update->leftJoin(
            $join,
            $expr->where()->raw(
                '? + 1 = ?',
                [
                    $expr->mod($this->getJoinColumn(), $sampleCount),
                    $expr->column('rownum', $joinAlias),
                ]
            ),
            $joinAlias
        );

        foreach ($columnOptions as $column) {
            $update->set(
                $this->options->get($column),
                $expr->column($column, $joinAlias)
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function clean(): void
    {
        if ($this->sampleTableName) {
            $this->connection->createSchemaManager()->dropTable($this->sampleTableName);
        }
    }
}
