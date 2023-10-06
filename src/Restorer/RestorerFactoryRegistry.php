<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class RestorerFactoryRegistry
{
    /** @var RestorerFactoryInterface[] */
    private array $restorerFactories;

    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private array $restorerBinaries,
    ) {}

    public function addRestorerFactory(RestorerFactoryInterface $factory): void
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

        if (!\array_key_exists($driver, $this->restorerBinaries)) {
            throw new \InvalidArgumentException(\sprintf(
                "There is no restorer binary provided for DBAL driver '%s'",
                $driver
            ));
        }

        foreach($this->restorerFactories as $restorerFactory) {
            if ($restorerFactory->isSupported($driver)) {
                return $restorerFactory->create(
                    $this->restorerBinaries[$driver],
                    $connection
                );
            }
        }

        throw new \InvalidArgumentException(\sprintf(
            "No restorer found for connection '%s'",
            $connectionName
        ));
    }
}
