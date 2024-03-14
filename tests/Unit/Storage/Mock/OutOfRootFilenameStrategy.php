<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Storage\Mock;

use Override;
use MakinaCorpus\DbToolsBundle\Storage\FilenameStrategyInterface;

class OutOfRootFilenameStrategy implements FilenameStrategyInterface
{
    public function __construct(
        private string $rootDir,
    ) {}

    #[Override]
    public function generateFilename(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $anonymized = false
    ): string {
        return $connectionName . '.' . $extension;
    }

    #[Override]
    public function getRootDir(
        string $defaultRootDir,
        string $connectionName = 'default',
    ): string {
        return $this->rootDir;
    }
}
