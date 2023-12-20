<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Platform;

class RestorerFactory
{
    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private array $restorerBinaries,
    ) {}

    /**
     * Get a Restorer for given connection
     *
     * @throws \InvalidArgumentException
     */
    public function create(?string $connectionName = null): AbstractRestorer
    {
        /** @var Connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        $queryBuilder = new DoctrineQueryBuilder($connection);
        $platform = $queryBuilder->getServerFlavor();

        $restorer = match ($platform) {
            Platform::MARIADB => MariaDB\Restorer::class,
            Platform::MYSQL => MySQL\Restorer::class,
            Platform::POSTGRESQL => PgSQL\Restorer::class,
            Platform::SQLITE => SQLite\Restorer::class,
            default => throw new NotImplementedException(\sprintf(
                "Restore is not implemented or configured for platform '%s' while using connection '%s'",
                $platform,
                $connectionName
            )),
        };

        return new $restorer(
            $this->restorerBinaries[$platform],
            $connection
        );
    }
}
