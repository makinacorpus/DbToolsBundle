<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Database;

use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Dsn;

/**
 * Main entry point for plugging this component to any framework.
 */
interface DatabaseSessionRegistry
{
    /**
     * Get all known connection names.
     *
     * @return string[]
     */
    public function getConnectionNames(): array;

    /**
     * Get default connection name.
     */
    public function getDefaultConnectionName(): string;

    /**
     * Get database connection information.
     */
    public function getConnectionDsn(string $name): Dsn;

    /**
     * Get database session for given connection name.
     */
    public function getDatabaseSession(string $name): DatabaseSession;
}
