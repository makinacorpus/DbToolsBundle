<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target as Target;

/**
 * Can not be use alone, check FrFR/PrenomAnonymizer for an
 * example on how to extends this Anonymizer for your need.
 */
class EnumAnonymizer extends AbstractAnonymizer
{
    /**
     * Overwrite this argument with your sample.
     */
    protected array $sample = [];

    /**
     * Overwrite this argument type of the column you want to
     * anonymize.
     */
    protected string $type = '';

    protected ?string $sampleTableName = null;

    /**
     * @inheritdoc
     */
    public function initialize(): self
    {
        $this->validateSample();
        $this->createSampleTempTable(
            ['value'],
            $this->sample,
            $this->getSampleTableName(),
            // Also handles types such as ''.
            $this->type ? [$this->type] : null,
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $query, Target\Target $target, Options $options): self
    {
        if (!$target instanceof Target\Column) {
            throw new \InvalidArgumentException("This anonymizer only accepts Target\Column target.");
        }

        $plateform = $this->connection->getDatabasePlatform();

        $random = $this->connection
            ->createQueryBuilder()
            ->select($this->getSampleTableName() . '.value')
            ->from($this->getSampleTableName())
            ->setMaxResults(1)
            ->where(
                $this->connection->createExpressionBuilder()->notLike(
                    $plateform->quoteIdentifier($target->table) . '.' . $target->column,
                    $this->getSampleTableName() . '.value'
                )
            )
            ->orderBy($this->getSqlRandomExpression())
        ;

        $query->set($plateform->quoteIdentifier($target->column), \sprintf('(%s)', $random));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clean(): self
    {
        $this->connection
            ->createSchemaManager()
            ->dropTable($this->getSampleTableName())
        ;

        return $this;
    }

    protected function getSampleTableName(): string
    {
        if ($this->sampleTableName) {
            return $this->sampleTableName;
        }

        return $this->sampleTableName = $this->generateTempTableName();
    }

    protected function validateSample(): self
    {
        /*
         * @todo
         *   Refactorer cette classe pour utiliser des méthodes plutôt que des
         *   propriétés protected.
         */
        /** @phpstan-ignore-next-line */
        if (\is_null($this->sample) || 0 === \count($this->sample)) {
            throw new \InvalidArgumentException("No sample given, your implementation of EnumAnomyzer should provide its own sample.");
        }

        return $this;
    }
}
