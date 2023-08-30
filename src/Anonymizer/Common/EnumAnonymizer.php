<?php


namespace MakinaCorpus\DbToolsBundle\Anonymizer\Common;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
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

        $this->connection
            ->createSchemaManager()
            ->createTable(new Table(
                $this->getSampleTableName(),
                [new Column('value', $this->getType())]
            ))
        ;

        $this->connection->beginTransaction();
        try{
            foreach($this->sample as $value) {
                $this->connection
                    ->createQueryBuilder()
                    ->insert($this->getSampleTableName())
                    ->setValue('value', ':value')
                    ->setParameter('value', $value)
                    ->executeQuery()
                ;
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }

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

    private function getSampleTableName(): string
    {
        if ($this->sampleTableName) {
            return $this->sampleTableName;
        }

        return $this->sampleTableName = $this->generateTempTableName();
    }

    private function getType(): Type
    {
        if ('' === $this->type) {
            throw new \InvalidArgumentException("No type given, your implementation of EnumAnomyzer should provide its own type.");
        }

        return Type::getType($this->type);
    }

    protected function validateSample(): self
    {
        if (\is_null($this->sample) || 0 === \count($this->sample)) {
            throw new \InvalidArgumentException("No sample given, your implementation of EnumAnomyzer should provide its own sample.");
        }

        return $this;
    }
}