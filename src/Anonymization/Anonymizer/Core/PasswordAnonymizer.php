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
    #[\Override]
    protected function validateOptions(): void
    {
        $algorithm = $this->options->getString('algorithm', 'auto');
        $this->options->getString('password', 'password');

        try {
            $passwordHasherFactory = new PasswordHasherFactory([
                $algorithm => ['algorithm' => $algorithm]
            ]);

            // We try to hash a password to validate given algorithm
            // really exists.
            $passwordHasherFactory
                ->getPasswordHasher($algorithm)
                ->hash('password')
            ;
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Given 'algorithm' option is invalid: " . $e->getMessage());
        }
    }

    #[\Override]
    public function anonymize(Update $update): void
    {
        $algorithm = $this->options->getString('algorithm', 'auto');
        $password = $this->options->getString('password', 'password');

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
