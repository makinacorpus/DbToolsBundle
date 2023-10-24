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
    /** @var LoaderInterface[] */
    private array $configurationLoaders = [];

    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private AnonymizerRegistry $anonymizerRegistry,
    ) {}

    public function addConfigurationLoader(LoaderInterface $loader): void
    {
        $this->configurationLoaders[] = $loader;
    }

    public function getOrCreate(string $connectionName): Anonymizator
    {
        if (isset($this->anonymizators[$connectionName])) {
            return $this->anonymizators[$connectionName];
        }

        /** @var Connection */
        $connection = $this->doctrineRegistry->getConnection($connectionName);

        $config = new AnonymizationConfig($connectionName);

        foreach ($this->configurationLoaders as $loader) {
            $loader->loadTo($config);
        }

        return $this->anonymizators[$connectionName] = new Anonymizator(
            $connectionName,
            $connection,
            $this->anonymizerRegistry,
            $config
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
