<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer\MySQL;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactoryInterface;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerInterface;

class RestorerFactory implements RestorerFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(string $binary, Connection $connection): RestorerInterface
    {
        return new Restorer($binary, $connection);
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported(string $driver): bool
    {
        return \str_contains($driver, 'mysql') || \str_contains($driver, 'maria');
    }
}
