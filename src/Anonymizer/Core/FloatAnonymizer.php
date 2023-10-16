<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonyze with a random Float between to bounds and with a given precision.
 */
#[AsAnonymizer('float')]
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
            \sprintf(
                'FLOOR(%s * (%s - %s + 1) + %s) / %s',
                $this->getSqlRandomExpression(),
                $max * $precision,
                $min * $precision,
                $min * $precision,
                $precision
            )
        );
    }
}
