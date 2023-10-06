<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target as Target;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonymize a string column by applying a MD5 function
 */
#[AsAnonymizer('md5')]
class Md5Anonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $query, Target\Target $target, Options $options): void
    {
        if (!$target instanceof Target\Column) {
            throw new \InvalidArgumentException("This anonymizer only accepts Target\Column target.");
        }

        $plateform = $this->connection->getDatabasePlatform();
        $quotedColumn = $plateform->quoteIdentifier($target->column);

        $query->set(
            $quotedColumn,
            'MD5(' . $quotedColumn . ')'
        );
    }
}
