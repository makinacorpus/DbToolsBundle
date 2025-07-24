<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Pack;

use MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer\Options;

abstract class PackAnonymizer
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $description,
        public readonly Options $options,
    ) {}
}
