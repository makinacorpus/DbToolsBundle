<?php

namespace MakinaCorpus\DbToolsBundle\Restorer;

use Doctrine\DBAL\Connection;

interface RestorerFactoryInterface
{
    public function create(string $binary, Connection $connection): RestorerInterface;

    /**
     * Check if given DBAL driver is supported by this restorer factory.
     */
    public function isSupported($driver): bool;
}
