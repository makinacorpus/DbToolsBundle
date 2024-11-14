<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Configuration;

class Configuration
{
    public const DEFAULT_BACKUP_EXPIRATION_AGE = '3 month ago';
    public const DEFAULT_BACKUP_TIMEOUT = 600;
    public const DEFAULT_DEFAULT_CONNECTION = 'default';
    public const DEFAULT_RESTORE_TIMEOUT = 1800;
    public const DEFAULT_STORAGE_FILENAME_STRATEGY = 'default';

    public function __construct(
        private readonly ?Configuration $parent = null,
        private readonly ?string $backupBinary = null,
        /** @var array<string> */
        private readonly ?array $backupExcludedTables = [],
        private readonly ?string $backupExpirationAge = null,
        private readonly ?string $backupOptions = null,
        private readonly null|int|string $backupTimeout = null,
        private readonly ?string $restoreBinary = null,
        private readonly ?string $restoreOptions = null,
        private readonly null|int|string $restoreTimeout = null,
        private readonly ?string $storageDirectory = null,
        private readonly ?string $storageFilenameStrategy = null,
        private readonly ?string $url = null,
    ) {}

    public function getBackupBinary(): ?string
    {
        return $this->backupBinary ?? $this->parent?->getBackupBinary();
    }

    public function getBackupExcludedTables(): array
    {
        return $this->backupExcludedTables ?? $this->parent?->getBackupExcludedTables() ?? [];
    }

    public function getBackupExpirationAge(): string
    {
        return $this->backupExpirationAge ?? $this->parent?->getBackupExpirationAge() ?? self::DEFAULT_BACKUP_EXPIRATION_AGE;
    }

    public function getBackupOptions(): ?string
    {
        return $this->backupOptions ?? $this->parent?->getBackupOptions();
    }

    public function getBackupTimeout(): int
    {
        return $this->backupTimeout ?? $this->parent?->getBackupTimeout() ?? self::DEFAULT_BACKUP_TIMEOUT;
    }

    public function getRestoreBinary(): ?string
    {
        return $this->restoreBinary ?? $this->parent?->getRestoreBinary();
    }

    public function getRestoreOptions(): ?string
    {
        return $this->restoreOptions ?? $this->parent?->getRestoreOptions();
    }

    public function getRestoreTimeout(): int
    {
        return $this->restoreTimeout ?? $this->parent?->getRestoreTimeout() ?? self::DEFAULT_RESTORE_TIMEOUT;
    }

    public function getStorageDirectory(): string
    {
        return $this->storageDirectory ?? $this->parent?->getStorageDirectory() ?? './var/db_tools';
    }

    public function getStorageFilenameStrategy(): string
    {
        return $this->storageFilenameStrategy ?? $this->parent?->getStorageFilenameStrategy() ?? self::DEFAULT_STORAGE_FILENAME_STRATEGY;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
