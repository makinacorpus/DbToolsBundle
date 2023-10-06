<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\Target;

abstract class Target
{
    public function __construct(
        public readonly string $table,
    ) {}
}
