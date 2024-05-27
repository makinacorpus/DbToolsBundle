<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Vendor;
use Psr\Log\LoggerInterface;

class BackupperFactory
{
    /**
     * Constructor.
     *
     * @param array<string, string> $backupperBinaries
     * @param array<string, string> $backupperOptions
     * @param array<string, string[]> $excludedTables
     */
    public function __construct(
        private DatabaseSessionRegistry $registry,
        private array $backupperBinaries,
        private array $backupperOptions = [],
        private array $excludedTables = [],
        private ?LoggerInterface $logger = null,
    ) {
        $connectionNames = $this->registry->getConnectionNames();

        // Normalize vendor names otherwise automatic creation might fail.
        foreach ($this->backupperBinaries as $vendorName => $binary) {
            $this->backupperBinaries[Vendor::vendorNameNormalize($vendorName)] = $binary;
        }

        foreach ($this->backupperOptions as $connectionName => $options) {
            if (!\in_array($connectionName, $connectionNames)) {
                throw new \DomainException(\sprintf(
                    "'%s' is not a valid connection name.",
                    $connectionName
                ));
            }
            if (!\is_string($options)) {
                throw new \InvalidArgumentException(
                    "Each value of the \$backupperOptions argument must be a string."
                );
            }
        }

        foreach ($this->excludedTables as $connectionName => $tableNames) {
            if (!\in_array($connectionName, $connectionNames)) {
                throw new \DomainException(\sprintf(
                    "'%s' is not a valid connection name.",
                    $connectionName
                ));
            }
            if (!\is_array($tableNames)) {
                throw new \InvalidArgumentException(
                    "Each value of the \$excludedTables argument must be an array of table names (strings)."
                );
            }
            foreach ($tableNames as $tableName) {
                if (!\is_string($tableName)) {
                    throw new \InvalidArgumentException(
                        "Each table name given through the \$excludedTables argument must be a string."
                    );
                }
            }
        }
    }

    /**
     * Get a Backupper for the given connection.
     *
     * @throws \InvalidArgumentException
     */
    public function create(?string $connectionName = null): AbstractBackupper
    {
        $connectionName ??= $this->registry->getDefaultConnectionName();
        $dsn = $this->registry->getConnectionDsn($connectionName);
        $vendorName = $dsn->getVendor();

        $backupper = match ($vendorName) {
            Vendor::MARIADB => MariadbBackupper::class,
            Vendor::MYSQL => MysqlBackupper::class,
            Vendor::POSTGRESQL => PgsqlBackupper::class,
            Vendor::SQLITE => SqliteBackupper::class,
            default => throw new NotImplementedException(\sprintf(
                "Backup is not implemented or configured for platform '%s' while using connection '%s'",
                $vendorName,
                $connectionName
            )),
        };

        $backupper = new $backupper(
            $this->registry->getDatabaseSession($connectionName),
            $dsn,
            $this->backupperBinaries[$vendorName] ?? null,
            $this->backupperOptions[$connectionName] ?? null
        );

        \assert($backupper instanceof AbstractBackupper);

        if (isset($this->excludedTables[$connectionName])) {
            $backupper->setExcludedTables($this->excludedTables[$connectionName]);
        }
        if ($this->logger) {
            $backupper->setLogger($this->logger);
        }

        return $backupper;
    }
}
