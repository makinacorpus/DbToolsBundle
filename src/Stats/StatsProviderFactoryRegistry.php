<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Stats;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class StatsProviderFactoryRegistry
{
    /** @var StatsProviderFactory[] */
    private array $instances = [];

    public function __construct(
        private ManagerRegistry $doctrineRegistry,
    ) {}

    public function register(StatsProviderFactory $instance): void
    {
        $this->instances[] = $instance;
    }

    /**
     * Get stats provider for given connection.
     */
    public function get(?string $connectionName = null): StatsProvider
    {
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        \assert($connection instanceof Connection);

        $driver = $connection->getParams()['driver'];

        foreach($this->instances as $instance) {
            if ($instance->isSupported($driver)) {
                return $instance->create($connection);
            }
        }

        throw new \InvalidArgumentException(\sprintf("No stats provider found for connection '%s'", $connectionName));
    }
}
