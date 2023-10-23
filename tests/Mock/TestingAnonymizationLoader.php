<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Mock;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;
use MakinaCorpus\DbToolsBundle\Anonymizer\Loader\LoaderInterface;

class TestingAnonymizationLoader implements LoaderInterface
{
    private AnonymizationConfig $config;

    public function __construct(?AnonymizationConfig $config = null)
    {
        $this->config = $config ?? new AnonymizationConfig();
    }

    public function load(string $connectionName): AnonymizationConfig
    {
        return $this->config;
    }
}