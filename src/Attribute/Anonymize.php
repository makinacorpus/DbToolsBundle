<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Attribute;

use Attribute;
/**
 * Service tag to autoconfigure anonymizations.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class Anonymize
{
    public function __construct(
        public string $type,
        public array $options = [],
    ) {}
}
