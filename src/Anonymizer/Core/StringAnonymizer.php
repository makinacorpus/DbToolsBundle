<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target as Target;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonymize a string column with a random value from a custom sample.
 *
 * For performance reason, If you use several time this anonymizer with the same sample,
 * you should consider to implement your own EnumAnonymizer.
 */
#[AsAnonymizer('string')]
class StringAnonymizer extends AbstractAnonymizer
{
    /** @var string[] */
    private array $tempTables = [];

    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $query, Target\Target $target, Options $options): void
    {
        if (!$target instanceof Target\Column) {
            throw new \InvalidArgumentException("This anonymizer only accepts Target\Column target.");
        }

        if (!$options->has('sample')) {
            throw new \InvalidArgumentException(\sprintf(
                <<<TXT
                You should provide an 'sample' option with this anonymizer.
                Check your configuration for table "%s", column "%s"
                TXT,
                $target->table,
                $target->column
            ));
        }
        $sample = $options->get('sample');

        $this->validateSample($target, $sample);

        $plateform = $this->connection->getDatabasePlatform();

        $this->createSampleTempTable(
            ['value'],
            $sample,
            $sampleTable = $this->generateTempTableName(),
            [Type::getType('string')],
        );

        $random = $this->connection
            ->createQueryBuilder()
            ->select($sampleTable . '.value')
            ->from($sampleTable)
            ->setMaxResults(1)
            ->where(
                $this->connection->createExpressionBuilder()->notLike(
                    $plateform->quoteIdentifier($target->table) . '.' . $target->column,
                    $sampleTable . '.value'
                )
            )
            ->orderBy($this->getSqlRandomExpression())
        ;

        $query->set($plateform->quoteIdentifier($target->column), \sprintf('(%s)', $random));
    }

    /**
     * @inheritdoc
     */
    public function clean(): void
    {
        $tableManager = $this->connection->createSchemaManager();

        foreach ($this->tempTables as $table) {
            $tableManager->dropTable($table);
        }
    }

    private function validateSample(Target\Column $target, $sample): self
    {
        if (\is_null($sample) || 0 === \count($sample)) {
            throw new \InvalidArgumentException(\sprintf(
                <<<TXT
                No sample given, or given sample is empty.
                Check your configuration for table "%s", column "%s"
                TXT,
                $target->table,
                $target->column
            ));
        }

        return $this;
    }
}
