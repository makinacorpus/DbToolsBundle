<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target as Target;
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
    public function anonymize(QueryBuilder $updateQuery, Target\Target $target, Options $options): void
    {
        if (!$target instanceof Target\Column) {
            throw new \InvalidArgumentException("This anonymazier only accepts Target\Column target.");
        }

        if (!($options->has('min') && $options->has('max'))) {
            throw new \InvalidArgumentException("You should provide 2 options (min and max) with this anonymizer");
        }

        $plateform = $this->connection->getDatabasePlatform();

        $max = $options->get('max');
        $min = $options->get('min');
        $precision = 10 ** $options->get('precision', 2);

        $updateQuery->set(
            $plateform->quoteIdentifier($target->column),
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
