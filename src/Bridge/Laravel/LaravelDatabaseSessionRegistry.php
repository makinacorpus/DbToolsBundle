<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Laravel;

use Doctrine\DBAL\Connection;
use Illuminate\Database\DatabaseManager;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineBridge;
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Dsn;

class LaravelDatabaseSessionRegistry implements DatabaseSessionRegistry
{
    public function __construct(
        private DatabaseManager $databaseManager,
    ) {}

    #[\Override]
    public function getConnectionNames(): array
    {
        return \array_keys($this->databaseManager->getConnections());
    }

    #[\Override]
    public function getDefaultConnectionName(): string
    {
        return $this->databaseManager->getDefaultConnection();
    }

    #[\Override]
    public function getConnectionDsn(string $name): Dsn
    {
        $params = \array_filter($this->databaseManager->connection($name)->getConfig());

        if (empty($params['driver'])) {
            throw new \DomainException("Database connection 'driver' parameter is missing.");
        }

        $vendor = $params['driver'];
        $host = $params['host'] ?? null;
        $filename = $params['path'] ?? $params['unix_socket'] ?? null;
        $database = $params['database'] ?? null;
        $user = $params['username'] ?? null;
        $password = $params['password'] ?? null;
        $port = (int) $params['port'] ?? null;

        unset(
            $params['database'],
            $params['driver'],
            $params['host'],
            $params['password'],
            $params['path'],
            $params['port'],
            $params['unix_socket'],
            $params['user'],
        );

        return new Dsn(
            database: $database,
            filename: $filename,
            host: $host,
            password: $password,
            port: $port,
            query: $params,
            user: $user,
            vendor: $vendor,
        );
    }

    #[\Override]
    public function getDatabaseSession(string $name): DatabaseSession
    {
        /** @var Connection $doctrineConnection */
        $doctrineConnection = $this
            ->databaseManager
            ->connection($name)
            ->getDoctrineConnection()
        ;

        return new DoctrineBridge($doctrineConnection);
    }
}
