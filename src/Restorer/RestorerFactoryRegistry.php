<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;

class RestorerFactoryRegistry
{
    /** @var RestorerFactoryInterface[] */
    private array $restorerFactories;

    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private array $restorerBinaries,
    ) {}

    public function register(RestorerFactoryInterface $factory): void
    {
        $this->restorerFactories[] = $factory;
    }

    /**
     * Get a Restorer for given connection
     *
     * @throws \InvalidArgumentException
     */
    public function create(?string $connectionName = null): RestorerInterface
    {
        /** @var Connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        $driver = $connection->getParams()['driver'];

        if (\array_key_exists($driver, $this->restorerBinaries)) {
            foreach ($this->restorerFactories as $restorerFactory) {
                if ($restorerFactory->isSupported($driver)) {
                    return $restorerFactory->create(
                        $this->restorerBinaries[$driver],
                        $connection
                    );
                }
            }
        }

        throw new NotImplementedException(\sprintf("Restore is not implemented or configured for driver '%s' while using connection '%s'", $driver, $connectionName));
    }
}
