<?php

declare(strict_types=1);

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
    public function isSupported(string $driver): bool
    {
        return \str_contains($driver, 'pgsql') || \str_contains($driver, 'postgres');
    }
}
