<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats;

use Doctrine\DBAL\Connection;

interface StatsProviderFactory
{
    /**
     * {@inheritdoc}
     */
    public function create(Connection $connection): StatsProvider;

    /**
     * {@inheritdoc}
     */
    public function isSupported(string $driver): bool;
}
