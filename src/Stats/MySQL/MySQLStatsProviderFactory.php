<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats\MySQL;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Stats\StatsProvider;
use MakinaCorpus\DbToolsBundle\Stats\StatsProviderFactory;

class MySQLStatsProviderFactory implements StatsProviderFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(Connection $connection): StatsProvider
    {
        return new MySQLStatsProvider($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported(string $driver): bool
    {
        return \str_contains($driver, 'mysql') || \str_contains($driver, 'maria');
    }
}
