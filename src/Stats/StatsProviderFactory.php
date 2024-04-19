<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Vendor;

class StatsProviderFactory
{
    public function __construct(
        private ManagerRegistry $doctrineRegistry,
    ) {}

    /**
     * Get stats provider for given connection.
     */
    public function create(?string $connectionName = null): AbstractStatsProvider
    {
        /** @var Connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        $session = new DoctrineQueryBuilder($connection);
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

        return new $statsProvider(new DoctrineQueryBuilder($connection));
    }
}
