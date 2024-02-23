<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Platform;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RestorerFactory
{
    /**
     * Constructor.
     *
     * @param array<string, string> $restorerBinaries
     * @param array<string, string> $restorerOptions
     */
    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private array $restorerBinaries,
        private array $restorerOptions = [],
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get a Restorer for given connection
     *
     * @throws \InvalidArgumentException
     */
    public function create(?string $connectionName = null): AbstractRestorer
    {
        /** @var Connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);
        $queryBuilder = new DoctrineQueryBuilder($connection);
        $platform = $queryBuilder->getServerFlavor();

        $restorer = match ($platform) {
            Platform::MARIADB => MariaDB\Restorer::class,
            Platform::MYSQL => MySQL\Restorer::class,
            Platform::POSTGRESQL => PgSQL\Restorer::class,
            Platform::SQLITE => SQLite\Restorer::class,
            default => throw new NotImplementedException(\sprintf(
                "Restore is not implemented or configured for platform '%s' while using connection '%s'",
                $platform,
                $connectionName
            )),
        };

        $restorer = new $restorer(
            $this->restorerBinaries[$platform],
            $connection,
            $this->restorerOptions[$connectionName] ?? null
        );

        \assert($restorer instanceof AbstractRestorer);

        if ($this->logger) {
            $restorer->addLogger($this->logger);
        }

        return $restorer;
    }
}
