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
    Values are salted to prevent reversing the hash with option 'use_salt' (default: true).
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
            $userExpr = $expr->column($this->columnName, $this->tableName);

            if ($this->options->get('use_salt', true)) {
                $userExpr = $expr->concat($userExpr, $expr->value($this->getSalt()));
            }

            $emailHashExpr = $expr->md5($userExpr);
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
