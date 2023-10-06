<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target as Target;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer('email')]
class EmailAnonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $updateQuery, Target\Target $target, Options $options): void
    {
        if (!$target instanceof Target\Column) {
            throw new \InvalidArgumentException("This anonymazier only accepts Target\Column target.");
        }

        $domain = $options->get('domain', 'example.com');

        $plateform = $this->connection->getDatabasePlatform();

        $quotedColumn = $plateform->quoteIdentifier($target->column);
        $updateQuery->set(
            $quotedColumn,
            $plateform->getConcatExpression(
                "'anon-'",
                'MD5(' . $quotedColumn . ')',
                "'@'",
                "'" . $domain . "'",
            )
        );
    }
}
