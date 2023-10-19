<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;

/**
 * Can not be use alone, check FrFR/PrenomAnonymizer for an
 * example on how to extends this Anonymizer for your need.
 */
abstract class EnumAnonymizer extends AbstractAnonymizer
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
        $this->createSampleTempTable(
            ['value'],
            $this->getSample(),
            $this->getSampleTableName(),
            // Also handles types such as ''.
            ($type = $this->getSampleType()) ? [$type] : null,
        );
    }

    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $query): void
    {
        $plateform = $this->connection->getDatabasePlatform();

        $random = $this->connection
            ->createQueryBuilder()
            ->select($this->getSampleTableName() . '.value')
            ->from($this->getSampleTableName())
            ->setMaxResults(1)
            ->where(
                $this->connection->createExpressionBuilder()->notLike(
                    $plateform->quoteIdentifier($this->tableName) . '.' . $plateform->quoteIdentifier($this->columnName),
                    $this->getSampleTableName() . '.value'
                )
            )
            ->orderBy($this->getSqlRandomExpression())
        ;

        $query->set($plateform->quoteIdentifier($this->columnName), \sprintf('(%s)', $random));
    }

    /**
     * @inheritdoc
     */
    public function clean(): void
    {
        $this->connection
            ->createSchemaManager()
            ->dropTable($this->getSampleTableName())
        ;
    }

    protected function getSampleTableName(): string
    {
        if ($this->sampleTableName) {
            return $this->sampleTableName;
        }

        return $this->sampleTableName = $this->generateTempTableName();
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
