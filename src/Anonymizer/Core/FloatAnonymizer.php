<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'float',
    pack: 'core',
    description: <<<TXT
    Anonymize with a random float between two bounds.
    Options are 'min' , 'max' and 'precision' (default 2).
    TXT
)]
class FloatAnonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $updateQuery): void
    {
        if (!($this->options->has('min') && $this->options->has('max'))) {
            throw new \InvalidArgumentException("You should provide 2 options (min and max) with this anonymizer");
        }

        $plateform = $this->connection->getDatabasePlatform();

        $max = $this->options->get('max');
        $min = $this->options->get('min');
        $precision = 10 ** $this->options->get('precision', 2);

        $updateQuery->set(
            $plateform->quoteIdentifier($this->columnName),
            $this->getSetIfNotNullExpression(
                $plateform->quoteIdentifier($this->columnName),
                \sprintf(
                    'FLOOR(%s * (%s - %s + 1) + %s) / %s',
                    $this->getSqlRandomExpression(),
                    $max * $precision,
                    $min * $precision,
                    $min * $precision,
                    $precision
                )
            )
        );
    }
}
