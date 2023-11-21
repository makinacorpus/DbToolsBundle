<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Config;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;

class AnonymizerConfig
{
    public function __construct(
        public readonly string $table,
        public readonly string $targetName,
        public readonly string $anonymizer,
        public readonly Options $options,
    ) {}
}
