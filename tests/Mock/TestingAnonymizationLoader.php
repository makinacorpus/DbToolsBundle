<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Mock;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizerConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Loader\LoaderInterface;

class TestingAnonymizationLoader implements LoaderInterface
{
    /**
     *  @param AnonymizerConfig[] $anonymizerConfigs
     */
    public function __construct(
        private array $anonymizerConfigs = []
    ) {}

    public function load(AnonymizationConfig $config): void
    {
        foreach ($this->anonymizerConfigs as $anonymizerConfig) {
            $config->add($anonymizerConfig);
        }
    }
}
