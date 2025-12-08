<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Symfony;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Dsn;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;

/**
 * doctrine/dbal based implementation.
 */
class DoctrineDatabaseSessionRegistry implements DatabaseSessionRegistry
{
    public function __construct(
        private ManagerRegistry $doctrineRegistry,
    ) {}

    #[\Override]
    public function getConnectionNames(): array
    {
        return \array_keys($this->doctrineRegistry->getConnections());
    }

    #[\Override]
    public function getDefaultConnectionName(): string
    {
        return $this->doctrineRegistry->getDefaultConnectionName();
    }

    #[\Override]
    public function getConnectionDsn(string $name): Dsn
    {
        $params = \array_filter($this->getDoctrineConnection($name)->getParams());

        if (empty($params['driver'])) {
            throw new NotImplementedException("Doctrine connection parameters do not expose the 'driver' key, further introspection is not implemented yet.");
        }
        $vendor = $params['driver'];

        $host = $params['host'] ?? null;
        $filename = $params['path'] ?? $params['unix_socket'] ?? null;
        $database = $params['dbname'] ?? null;
        $user = $params['user'] ?? null;
        $password = $params['password'] ?? null;
        $port = $params['port'] ?? null;

        unset(
            $params['dbname'],
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
        return new DoctrineQueryBuilder($this->getDoctrineConnection($name));
    }

    protected function getDoctrineConnection(string $name): Connection
    {
        return $this->doctrineRegistry->getConnection($name);
    }
}
