<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Loader;

use MakinaCorpus\DbToolsBundle\Anonymizer\AnonymizationConfig;

interface LoaderInterface
{
    public function loadTo(AnonymizationConfig $config): void;
}
