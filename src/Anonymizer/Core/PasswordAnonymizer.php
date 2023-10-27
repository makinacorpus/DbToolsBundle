<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Core;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

#[AsAnonymizer(
    name: 'password',
    pack: 'core',
    description: <<<TXT
    Anonymize a password erasing it with a given value.
    Options are 'algorithm' (default 'auto') and 'password' (default 'password').
    TXT
)]
class PasswordAnonymizer extends AbstractAnonymizer
{
    /**
     * @inheritdoc
     */
    public function anonymize(QueryBuilder $updateQuery): void
    {
        $algorithm = $this->options->get('algorithm', 'auto');
        $password = $this->options->get('password', 'password');

        $passwordHasherFactory = new PasswordHasherFactory([
            $algorithm => ['algorithm' => $algorithm]
        ]);
        $passwordHasher = $passwordHasherFactory->getPasswordHasher($algorithm);
        $hashedPassword = $passwordHasher->hash($password);

        $plateform = $this->connection->getDatabasePlatform();

        $quotedColumn = $plateform->quoteIdentifier($this->columnName);
        $updateQuery->set(
            $quotedColumn,
            $this->getSetIfNotNullExpression(
                $quotedColumn,
                $plateform->quoteStringLiteral($hashedPassword)
            )
        );
    }
}
