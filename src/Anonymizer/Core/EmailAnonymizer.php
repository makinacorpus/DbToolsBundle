<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'email',
    pack: 'core',
    description: <<<TXT
    Anonymize email addresses.
    You can choose a domain and a tld with option `domain`.
    TXT
)]
class EmailAnonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $updateQuery): void
    {
        $domain = $this->options->get('domain', 'example.com');

        $plateform = $this->connection->getDatabasePlatform();

        $quotedColumn = $plateform->quoteIdentifier($this->columnName);
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
