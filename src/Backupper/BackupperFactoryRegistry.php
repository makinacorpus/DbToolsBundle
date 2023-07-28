<?php


namespace MakinaCorpus\DbToolsBundle\Backupper;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class BackupperFactoryRegistry
{
    /** @var BackupperFactoryInterface[] */
    private array $backupperFactories;

    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private array $backupperBinaries,
    ) { }

    public function addBackupperFactory(BackupperFactoryInterface $factory): void
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

        if (!\array_key_exists($driver, $this->backupperBinaries)) {
            throw new \InvalidArgumentException(\sprintf(
                "There is no backupper binary provided for DBAL driver '%s'",
                $driver
            ));
        }

        foreach($this->backupperFactories as $backupperFactory) {
            if ($backupperFactory->isSupported($driver)) {
                return $backupperFactory->create(
                    $this->backupperBinaries[$driver],
                    $connection
                );
            }
        }

        throw new \InvalidArgumentException(\sprintf(
            "No backupper found for connection '%s'",
            $connectionName
        ));
    }
}