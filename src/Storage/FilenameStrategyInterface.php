<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Storage;

interface FilenameStrategyInterface
{
    /**
     * Generate dump filename relative to configured root directory.
     */
    public function generateFilename(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $anonymized = false
    ): string;

    /**
     * If your strategy doesn't store backups in the configured root directory
     * then return here a root path in which backups will be lookup by the
     * storage manager when the restore command is run.
     */
    public function getRootDir(
        string $defaultRootDir,
        string $connectionName = 'default',
    ): string;
}
