<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader\LoaderInterface;
use MakinaCorpus\DbToolsBundle\Helper\Output\ConsoleOutput;
use Psr\Log\LoggerInterface;

class AnonymizatorFactory
{
    /** @var Array<string, Anonymizator> */
    private array $anonymizators = [];
    /** @var LoaderInterface[] */
    private array $configurationLoaders = [];

    public function __construct(
        private ManagerRegistry $doctrineRegistry,
        private AnonymizerRegistry $anonymizerRegistry,
        private ?LoggerInterface $logger = null,
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

        $connection = $this->doctrineRegistry->getConnection($connectionName);
        \assert($connection instanceof Connection);

        $config = new AnonymizationConfig($connectionName);

        foreach ($this->configurationLoaders as $loader) {
            $loader->load($config);
        }

        $anonymizator = new Anonymizator(
            $connection,
            $this->anonymizerRegistry,
            $config
        );

        if ($this->logger) {
            $anonymizator->addLogger($this->logger);
        }

        return $this->anonymizators[$connectionName] = $anonymizator;
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
