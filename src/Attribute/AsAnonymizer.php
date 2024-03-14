<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Attribute;

use Attribute;
/**
 * Service tag to autoconfigure anonymizers.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AsAnonymizer
{
    public function __construct(
        public string $name,
        public string $pack,
        public ?string $description = null,
    ) {}

    public function id(): string
    {
        // Id is 'pack.name' except for anonymiser from core
        // which don't have prefix.
        return ('core' !== $this->pack ? $this->pack . '.' : '') . $this->name;
    }
}
