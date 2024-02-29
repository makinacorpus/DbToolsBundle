<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Platform;

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
        $queryBuilder = new DoctrineQueryBuilder($connection);
        $platform = $queryBuilder->getServerFlavor();

        $statsProvider = match ($platform) {
            Platform::POSTGRESQL => PgsqlStatsProvider::class,
            Platform::MYSQL, Platform::MARIADB => MysqlStatsProvider::class,
            default => throw new NotImplementedException(\sprintf(
                "Stat collection is not implemented for platform '%s' while using connection '%s'",
                $platform,
                $connectionName
            )),
        };

        return new $statsProvider(
            $connection
        );
    }
}
