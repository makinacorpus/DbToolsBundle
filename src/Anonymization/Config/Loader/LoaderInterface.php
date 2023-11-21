<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\AnonymizationConfig;

interface LoaderInterface
{
    /**
     * Load AnonymizerConfiguration to an existing AnonymizationConfig.
     */
    public function load(AnonymizationConfig $config): void;
}
