<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

#[AsAnonymizer(
    name: 'md5',
    pack: 'core',
    description: 'Anonymize a column by hashing its value.'
)]
class Md5Anonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $query): void
    {
        $plateform = $this->connection->getDatabasePlatform();
        $quotedColumn = $plateform->quoteIdentifier($this->columnName);

        $query->set($quotedColumn, 'MD5(' . $quotedColumn . ')');
    }
}
