<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Platform;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BackupperFactory
{
    /**
     * Constructor.
     *
     * @param array<string, string> $backupperBinaries
     * @param array<string, string> $backupperOptions
     */
    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private array $backupperBinaries,
        private array $backupperOptions = [],
        private ?LoggerInterface $logger = null,
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

        $backupper = new $backupper(
            $this->backupperBinaries[$platform],
            $connection,
            $this->backupperOptions[$connectionName] ?? null
        );

        \assert($backupper instanceof AbstractBackupper);

        if ($this->logger) {
            $backupper->addLogger($this->logger);
        }

        return $backupper;
    }
}
