<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Configuration\ConfigurationRegistry;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Vendor;
use Psr\Log\LoggerInterface;

class RestorerFactory
{
    public function __construct(
        private DatabaseSessionRegistry $registry,
        private ConfigurationRegistry $configRegistry = new ConfigurationRegistry(),
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get a Restorer for given connection
     *
     * @throws \InvalidArgumentException
     */
    public function create(?string $connectionName = null): AbstractRestorer
    {
        $connectionName ??= $this->registry->getDefaultConnectionName();
        $dsn = $this->registry->getConnectionDsn($connectionName);
        $vendorName = $dsn->getVendor();

        $restorer = match ($vendorName) {
            Vendor::MARIADB => MariadbRestorer::class,
            Vendor::MYSQL => MysqlRestorer::class,
            Vendor::POSTGRESQL => PgsqlRestorer::class,
            Vendor::SQLITE => SqliteRestorer::class,
            default => throw new NotImplementedException(\sprintf(
                "Restore is not implemented or configured for platform '%s' while using connection '%s'",
                $vendorName,
                $connectionName
            )),
        };

        $restorer = new $restorer(
            $this->registry->getDatabaseSession($connectionName),
            $dsn,
            $this->configRegistry->getConnectionConfig($connectionName),
        );

        \assert($restorer instanceof AbstractRestorer);

        if ($this->logger) {
            $restorer->setLogger($this->logger);
        }

        return $restorer;
    }
}
