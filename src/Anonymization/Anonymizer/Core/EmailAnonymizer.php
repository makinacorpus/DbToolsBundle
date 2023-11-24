<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;

#[AsAnonymizer(
    name: 'email',
    pack: 'core',
    description: <<<TXT
    Anonymize email addresses. You can choose a domain and a tld with option 'domain'.
    TXT
)]
class EmailAnonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(Update $update): void
    {
        $expr = $update->expression();

        if ($this->connection->getDatabasePlatform() instanceof SqlitePlatform) {
            $emailHashExpr = $this->getJoinColumn();
        } else {
            $emailHashExpr = $expr->md5($expr->column($this->columnName, $this->tableName));
        }

        $update->set(
            $this->columnName,
            $this->getSetIfNotNullExpression(
                $expr->concat(
                    'anon-',
                    $emailHashExpr,
                    '@',
                    $this->options->get('domain', 'example.com'),
                ),
            ),
        );
    }
}
