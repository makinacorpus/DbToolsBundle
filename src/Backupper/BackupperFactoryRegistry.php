<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;

class BackupperFactoryRegistry
{
    /** @var BackupperFactoryInterface[] */
    private array $backupperFactories;

    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private array $backupperBinaries,
    ) {}

    public function register(BackupperFactoryInterface $factory): void
    {
        $this->backupperFactories[] = $factory;
    }

    /**
     * Get a Backupper for given connection
     *
     * @throws \InvalidArgumentException
     */
    public function create(?string $connectionName = null): BackupperInterface
    {
        /** @var Connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        $driver = $connection->getParams()['driver'];

        if (\array_key_exists($driver, $this->backupperBinaries)) {
            foreach ($this->backupperFactories as $backupperFactory) {
                if ($backupperFactory->isSupported($driver)) {
                    return $backupperFactory->create(
                        $this->backupperBinaries[$driver],
                        $connection
                    );
                }
            }
        }

        throw new NotImplementedException(\sprintf("Backup is not implemented or configured for driver '%s' while using connection '%s'", $driver, $connectionName));
    }
}
