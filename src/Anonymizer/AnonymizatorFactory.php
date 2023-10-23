<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymizer\Loader\LoaderInterface;

class AnonymizatorFactory
{
    /** @var Array<string, Anonymizator> */
    private array $anonymizators = [];

    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private AnonymizerRegistry $anonymizerRegistry,
        private LoaderInterface $configurationLoader,
    ) {}

    public function getOrCreate(string $connectionName): Anonymizator
    {
        if (isset($this->anonymizators[$connectionName])) {
            $this->anonymizators[$connectionName];
        }

        /** @var Connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);

        return $this->anonymizators[$connectionName] = new Anonymizator(
            $connectionName,
            $connection,
            $this->anonymizerRegistry,
            $this->configurationLoader->load($connectionName)
        );
    }

    /**
     * @return array<string, Anonymizator>
     */
    public function all(): array
    {
        $ret = [];
        foreach (\array_keys($this->doctrineRegistry->getConnections()) as $connectionName) {
            $ret[$connectionName] = $this->getOrCreate($connectionName);
        }

        return $ret;
    }
}
