<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Mock;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader\LoaderInterface;

class TestingAnonymizationLoader implements LoaderInterface
{
    /**
     *  @param AnonymizerConfig[] $anonymizerConfigs
     */
    public function __construct(
        private array $anonymizerConfigs = []
    ) {}

    #[\Override]
    public function load(AnonymizationConfig $config): void
    {
        foreach ($this->anonymizerConfigs as $anonymizerConfig) {
            $config->add($anonymizerConfig);
        }
    }
}
