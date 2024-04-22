<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Database;

use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\DatabaseSession;

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
    public function getDatabaseSession(string $connectionName): DatabaseSession
    {
        return new DoctrineQueryBuilder($this->doctrineRegistry->getConnection($connectionName));
    }
}
