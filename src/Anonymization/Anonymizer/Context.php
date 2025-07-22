<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

class Context
{
    public readonly string $salt;
    public readonly string $basePath;

    public function __construct(
        ?string $salt = null,
        ?string $basePath = null,
    ) {
        $this->salt = $salt ?? self::generateRandomSalt();
        $this->basePath = $basePath ?? \getcwd();
    }

    public static function generateRandomSalt(): string
    {
        return \base64_encode(\random_bytes(12));
    }
}
