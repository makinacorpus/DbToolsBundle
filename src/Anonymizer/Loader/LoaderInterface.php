<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Loader;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;

interface LoaderInterface
{
    /**
     * Load AnonymizerConfiguration to an existing AnonymizationConfig.
     */
    public function loadTo(AnonymizationConfig $config): void;
}
