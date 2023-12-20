<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Platform;

class BackupperFactory
{
    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private array $backupperBinaries,
    ) {}

    /**
     * Get a Backupper for given connection
     *
     * @throws \InvalidArgumentException
     */
    public function create(?string $connectionName = null): AbstractBackupper
    {
        /** @var Connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        $queryBuilder = new DoctrineQueryBuilder($connection);
        $platform = $queryBuilder->getServerFlavor();

        $backupper = match ($platform) {
            Platform::MARIADB => MariaDB\Backupper::class,
            Platform::MYSQL => MySQL\Backupper::class,
            Platform::POSTGRESQL => PgSQL\Backupper::class,
            Platform::SQLITE => SQLite\Backupper::class,
            default => throw new NotImplementedException(\sprintf(
                "Backup is not implemented or configured for platform '%s' while using connection '%s'",
                $platform,
                $connectionName
            )),
        };

        return new $backupper(
            $this->backupperBinaries[$platform],
            $connection
        );
    }
}
