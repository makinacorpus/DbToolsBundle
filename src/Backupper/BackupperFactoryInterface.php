<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use Doctrine\DBAL\Connection;

interface BackupperFactoryInterface
{
    public function create(string $binary, Connection $connection): BackupperInterface;

    /**
     * Check if given DBAL driver is supported by this backupper factory.
     */
    public function isSupported(string $driver): bool;
}
