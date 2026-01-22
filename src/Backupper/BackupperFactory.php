<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use MakinaCorpus\DbToolsBundle\Configuration\ConfigurationRegistry;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Vendor;
use Psr\Log\LoggerInterface;

class BackupperFactory
{
    public function __construct(
        private DatabaseSessionRegistry $registry,
        private ConfigurationRegistry $configRegistry = new ConfigurationRegistry(),
        private ?LoggerInterface $logger = null,
    ) {}

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
            $this->configRegistry->getConnectionConfig($connectionName),
        );

        if (isset($this->excludedTables[$connectionName])) {
            $backupper->setExcludedTables($this->excludedTables[$connectionName]);
        }
        if ($this->logger) {
            $backupper->setLogger($this->logger);
        }

        return $backupper;
    }
}
