<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

class AnonymizationSingleConfig
{
    public function __construct(
        public string $table,
        public string $targetName,
        public string $anonymizer,
        public Options $options,
    ) {}
}
