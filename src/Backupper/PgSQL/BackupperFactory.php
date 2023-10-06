<?php

namespace MakinaCorpus\DbToolsBundle\Backupper\PgSQL;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactoryInterface;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperInterface;

class BackupperFactory implements BackupperFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(string $binary, Connection $connection): BackupperInterface
    {
        return new Backupper($binary, $connection);
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($driver): bool
    {
        return \in_array($driver, ['pdo_pgsql', 'pgsql']);
    }
}
