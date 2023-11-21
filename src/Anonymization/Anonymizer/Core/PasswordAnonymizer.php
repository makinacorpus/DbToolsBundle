<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Core;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;
use MakinaCorpus\QueryBuilder\Query\Update;
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
    public function anonymize(Update $update): void
    {
        $algorithm = $this->options->get('algorithm', 'auto');
        $password = $this->options->get('password', 'password');

        $passwordHasherFactory = new PasswordHasherFactory([
            $algorithm => ['algorithm' => $algorithm]
        ]);
        $passwordHasher = $passwordHasherFactory->getPasswordHasher($algorithm);
        $hashedPassword = $passwordHasher->hash($password);

        $update->set(
            $this->columnName,
            $this->getSetIfNotNullExpression(
                $hashedPassword
            )
        );
    }
}
