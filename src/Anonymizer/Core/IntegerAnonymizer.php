<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'integer',
    pack: 'core',
    description: <<<TXT
    Anonymize with a random integer between two bounds.
    Options are `min` , `max`.
    TXT
)]
class IntegerAnonymizer extends AbstractAnonymizer
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


        $updateQuery->set(
            $plateform->quoteIdentifier($this->columnName),
            \sprintf(
                'FLOOR(%s*(%s-%s+1)+%s)',
                $this->getSqlRandomExpression(),
                $this->options->get('max'),
                $this->options->get('min'),
                $this->options->get('min')
            )
        );
    }
}
