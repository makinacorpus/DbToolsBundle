<?php


namespace MakinaCorpus\DbToolsBundle\Anonymizer\Common;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target as Target;

/**
 * Anonymize a string column by applying a MD5 function
 */
class Md5Anonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $query, Target\Target $target, Options $options): self
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

        return $this;
    }
}