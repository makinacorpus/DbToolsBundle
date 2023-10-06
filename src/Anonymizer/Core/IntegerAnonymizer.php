<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target as Target;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonyze with a random integer between to bounds.
 */
#[AsAnonymizer('integer')]
class IntegerAnonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $updateQuery, Target\Target $target, Options $options): self
    {
        if (!$target instanceof Target\Column) {
            throw new \InvalidArgumentException("This anonymazier only accepts Target\Column target.");
        }

        if (!($options->has('min') && $options->has('max'))) {
            throw new \InvalidArgumentException("You should provide 2 options (min and max) with this anonymizer");
        }

        $plateform = $this->connection->getDatabasePlatform();


        $updateQuery->set(
            $plateform->quoteIdentifier($target->column),
            \sprintf(
                'FLOOR(%s*(%s-%s+1)+%s)',
                $this->getSqlRandomExpression(),
                $options->get('max'),
                $options->get('min'),
                $options->get('min')
            )
        );

        return $this;
    }
}