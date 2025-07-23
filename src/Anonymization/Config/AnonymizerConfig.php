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
        /**
         * Root directory in which this anonymizer configuration was in the
         * first place. This allows implementations to use it in order, for
         * example, to load files using a relative path.
         */
        public readonly string $basePath,
    ) {}
}
