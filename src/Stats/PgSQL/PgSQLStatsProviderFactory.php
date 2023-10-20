<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats\PgSQL;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Stats\StatsProvider;
use MakinaCorpus\DbToolsBundle\Stats\StatsProviderFactory;

class PgSQLStatsProviderFactory implements StatsProviderFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(Connection $connection): StatsProvider
    {
        return new PgSQLStatsProvider($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported(string $driver): bool
    {
        return \str_contains($driver, 'pgsql') || \str_contains($driver, 'postgres');
    }
}
