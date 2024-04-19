<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Vendor;
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
    #[\Override]
    protected function validateOptions(): void
    {
        $this->options->getString('domain', 'example.com', true);
        $this->options->getBool('use_salt', false);
    }

    #[\Override]
    public function anonymize(Update $update): void
    {
        $expr = $update->expression();

        if ($this->databaseSession->vendorIs(Vendor::SQLITE)) {
            $emailHashExpr = $this->getJoinColumn();
        } else {
            $userExpr = $expr->column($this->columnName, $this->tableName);

            if ($this->options->getBool('use_salt', true)) {
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
