<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Storage;

use Override;
abstract class AbstractFilenameStrategy implements FilenameStrategyInterface
{
    #[Override]
    public function getRootDir(
        string $defaultRootDir,
        string $connectionName = 'default',
    ): string {
        return $defaultRootDir . '/' . $connectionName;
    }
}
