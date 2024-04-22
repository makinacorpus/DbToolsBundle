<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Database;

use MakinaCorpus\QueryBuilder\DatabaseSession;

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
     * Get database session for given connection name.
     */
    public function getDatabaseSession(string $connectionName): DatabaseSession;
}
