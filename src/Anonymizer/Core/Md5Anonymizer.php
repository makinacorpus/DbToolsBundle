<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonymize a string column by applying a MD5 function.
 */
#[AsAnonymizer('md5')]
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
