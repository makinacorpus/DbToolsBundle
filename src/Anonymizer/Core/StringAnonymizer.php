<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonymize a string column with a random value from a custom sample.
 *
 * If you need to generate a complex sample, you should consider to
 * implement your own EnumAnonymizer.
 */
#[AsAnonymizer(
    name: 'string',
    pack: 'core',
    description: 'Anonymize a column by setting a random value from a given sample option.'
)]
class StringAnonymizer extends AbstractAnonymizer
{
    /** @var string[] */
    private array $tempTables = [];

    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $query): void
    {
        if (!$this->options->has('sample')) {
            throw new \InvalidArgumentException(\sprintf(
                <<<TXT
                You should provide an 'sample' option with this anonymizer.
                Check your configuration for table "%s", column "%s"
                TXT,
                $this->tableName,
                $this->columnName,
            ));
        }
        $sample = $this->options->get('sample');

        $this->validateSample($sample);

        $plateform = $this->connection->getDatabasePlatform();

        $this->createSampleTempTable(
            ['value'],
            $sample,
            $sampleTable = $this->generateTempTableName(),
            [Type::getType('text')],
        );

        $random = $this->connection
            ->createQueryBuilder()
            ->select($sampleTable . '.value')
            ->from($sampleTable)
            ->setMaxResults(1)
            ->where(
                $this->connection->createExpressionBuilder()->notLike(
                    $plateform->quoteIdentifier($this->tableName) . '.' . $plateform->quoteIdentifier($this->columnName),
                    $sampleTable . '.value'
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
        $tableManager = $this->connection->createSchemaManager();

        foreach ($this->tempTables as $table) {
            $tableManager->dropTable($table);
        }
    }

    private function validateSample(mixed $sample): self
    {
        if (\is_null($sample) || 0 === \count($sample)) {
            throw new \InvalidArgumentException(\sprintf(
                <<<TXT
                No sample given, or given sample is empty.
                Check your configuration for table "%s", column "%s"
                TXT,
                $this->tableName,
                $this->columnName
            ));
        }

        return $this;
    }
}
