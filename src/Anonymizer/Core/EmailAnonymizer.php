<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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
    public function anonymize(QueryBuilder $updateQuery, Target\Target $target, Options $options): self
    {
        if (!$target instanceof Target\Column) {
            throw new \InvalidArgumentException("This anonymazier only accepts Target\Column target.");
        }

        $domain = $options->has('domain') ? $options->get('domain') : 'example.com';

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

        return $this;
    }

    private function getRandom(): string
    {
        $plateform = $this->connection->getDatabasePlatform();

        return match (true) {
            $plateform instanceof MySQLPlatform => "rand()",
            $plateform instanceof PostgreSQLPlatform => "random()",
            default => throw new \InvalidArgumentException(\sprintf('%s is not supported.', \get_class($plateform)))
        };
    }
}