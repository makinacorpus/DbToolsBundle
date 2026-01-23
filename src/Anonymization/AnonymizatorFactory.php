<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\AnonymizerRegistry;
use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Context;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader\LoaderInterface;
use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use Psr\Log\LoggerInterface;

class AnonymizatorFactory
{
    /** @var Array<string, Anonymizator> */
    private array $anonymizators = [];
    /** @var LoaderInterface[] */
    private array $configurationLoaders = [];

    public function __construct(
        private DatabaseSessionRegistry $registry,
        private AnonymizerRegistry $anonymizerRegistry,
        private ?LoggerInterface $logger = null,
        /**
         * @todo
         *   This is not the right place to set this, but any other alternative
         *   would require a deep refactor of anonymizer options.
         */
        private ?string $basePath = null,
    ) {}

    /**
     * @internal
     *   For Laravel dependency injection only.
     *   This can change anytime.
     */
    public function setBasePath(?string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * Add configuration loader.
     */
    public function addConfigurationLoader(LoaderInterface $loader): void
    {
        $this->configurationLoaders[] = $loader;
    }

    /**
     * Get anonymizator instance for given connection name.
     */
    public function getOrCreate(string $connectionName): Anonymizator
    {
        if (isset($this->anonymizators[$connectionName])) {
            return $this->anonymizators[$connectionName];
        }

        $config = new AnonymizationConfig($connectionName);

        foreach ($this->configurationLoaders as $loader) {
            $loader->load($config);
        }

        $anonymizator = new Anonymizator(
            $this->registry->getDatabaseSession($connectionName),
            $this->anonymizerRegistry,
            $config,
            new Context(basePath: $this->basePath),
        );

        if ($this->logger) {
            $anonymizator->setLogger($this->logger);
        }

        return $this->anonymizators[$connectionName] = $anonymizator;
    }

    /**
     * Get all anonymizators for each known connection name.
     *
     * @return array<string, Anonymizator>
     */
    public function all(): array
    {
        $ret = [];
        foreach ($this->registry->getConnectionNames() as $connectionName) {
            $ret[$connectionName] = $this->getOrCreate($connectionName);
        }

        return $ret;
    }
}
