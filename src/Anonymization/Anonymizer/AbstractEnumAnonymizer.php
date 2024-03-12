<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

use MakinaCorpus\QueryBuilder\Query\Select;
use MakinaCorpus\QueryBuilder\Query\Update;

/**
 * Can not be use alone, check FrFR/PrenomAnonymizer for an
 * example on how to extend this Anonymizer for your need.
 */
abstract class AbstractEnumAnonymizer extends AbstractAnonymizer
{
    private ?string $sampleTableName = null;

    /**
     * Overwrite this argument with your sample.
     */
    protected function getSampleType(): string
    {
        return 'text';
    }

    /**
     * Overwrite this argument with your sample.
     */
    abstract protected function getSample(): array;

    /**
     * @inheritdoc
     */
    public function initialize(): void
    {
        $this->validateSample();

        $this->sampleTableName = $this->createSampleTempTable(
            ['value'],
            $this->getSample(),
            // Also handles types such as ''.
            ($type = $this->getSampleType()) ? [$type] : null,
        );
    }

    /**
     * @inheritdoc
     */
    public function anonymize(Update $update): void
    {
        $expr = $update->expression();

        $targetCount = $this->countTable($this->tableName);
        $sampleCount = $this->countTable($this->sampleTableName);

        $joinAlias = $this->sampleTableName . '_' . $this->columnName;
        $join = (new Select($this->sampleTableName))
            ->column('value')
            ->columnRaw('ROW_NUMBER() OVER (ORDER BY ?)', 'rownum', [$expr->random()])
            ->range($targetCount) // Avoid duplicate rows.
        ;

        $update->leftJoin(
            $join,
            $expr
                ->where()
                ->raw(
                    '? + 1 = ?',
                    [
                        $expr->mod($this->getJoinColumn(), \min($targetCount, $sampleCount)),
                        $expr->column('rownum', $joinAlias),
                    ]
                )
                ->isNotNull($expr->column($this->columnName, self::JOIN_TABLE)),
            $joinAlias
        );

        $update->set($this->columnName, $expr->column('value', $joinAlias));
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

    protected function validateSample(): void
    {
        $sample = $this->getSample();

        /*
         * @todo
         *   Refactorer cette classe pour utiliser des méthodes plutôt que des
         *   propriétés protected.
         */
        /** @phpstan-ignore-next-line */
        if (\is_null($sample) || 0 === \count($sample)) {
            throw new \InvalidArgumentException("No sample given, your implementation of EnumAnomyzer should provide its own sample.");
        }
    }
}
