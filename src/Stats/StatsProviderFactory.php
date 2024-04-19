<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats;

use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Vendor;

class StatsProviderFactory
{
    public function __construct(
        private DatabaseSessionRegistry $registry,
    ) {}

    /**
     * Get stats provider for given connection.
     */
    public function create(?string $connectionName = null): AbstractStatsProvider
    {
        $session = $this->registry->getDatabaseSession($connectionName);
        $vendorName = $session->getVendorName();

        $statsProvider = match ($vendorName) {
            Vendor::POSTGRESQL => PgsqlStatsProvider::class,
            Vendor::MYSQL, Vendor::MARIADB => MysqlStatsProvider::class,
            default => throw new NotImplementedException(\sprintf(
                "Stat collection is not implemented for platform '%s' while using connection '%s'",
                $vendorName,
                $connectionName
            )),
        };

        return new $statsProvider($session);
    }
}
