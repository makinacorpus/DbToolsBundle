<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Vendor;
use Psr\Log\LoggerInterface;

class RestorerFactory
{
    /**
     * Constructor.
     *
     * @param array<string, string> $restorerBinaries
     * @param array<string, string> $restorerOptions
     */
    public function __construct(
        private DatabaseSessionRegistry $registry,
        private array $restorerBinaries,
        private array $restorerOptions = [],
        private ?LoggerInterface $logger = null,
    ) {
        // Normalize vendor names otherwise automatic creation might fail.
        foreach ($this->restorerBinaries as $vendorName => $binary) {
            $this->restorerBinaries[Vendor::vendorNameNormalize($vendorName)] = $binary;
        }
    }

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
            $this->restorerBinaries[$vendorName],
            $this->registry->getDatabaseSession($connectionName),
            $dsn,
            $this->restorerOptions[$connectionName] ?? null
        );

        \assert($restorer instanceof AbstractRestorer);

        if ($this->logger) {
            $restorer->setLogger($this->logger);
        }

        return $restorer;
    }
}
