<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Platform;
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
        private ManagerRegistry $doctrineRegistry,
        private array $backupperBinaries,
        private array $backupperOptions = [],
        private array $excludedTables = [],
        private ?LoggerInterface $logger = null,
    ) {
        $connectionNames = $this->doctrineRegistry->getConnectionNames();

        foreach ($this->backupperOptions as $connectionName => $options) {
            if (!\in_array($connectionName, $connectionNames, true)) {
                throw new \DomainException(\sprintf(
                    "'%s' is not a valid Doctrine connection name.",
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
            if (!\in_array($connectionName, $connectionNames, true)) {
                throw new \DomainException(\sprintf(
                    "'%s' is not a valid Doctrine connection name.",
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
        $connectionName ??= $this->doctrineRegistry->getDefaultConnectionName();
        /** @var Connection $connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        $queryBuilder = new DoctrineQueryBuilder($connection);
        $platform = $queryBuilder->getServerFlavor();

        $backupper = match ($platform) {
            Platform::MARIADB => MariadbBackupper::class,
            Platform::MYSQL => MysqlBackupper::class,
            Platform::POSTGRESQL => PgsqlBackupper::class,
            Platform::SQLITE => SqliteBackupper::class,
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

        if (isset($this->excludedTables[$connectionName])) {
            $backupper->setExcludedTables($this->excludedTables[$connectionName]);
        }
        if ($this->logger) {
            $backupper->setLogger($this->logger);
        }

        return $backupper;
    }
}
