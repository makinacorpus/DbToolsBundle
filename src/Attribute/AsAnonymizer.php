<?php

namespace MakinaCorpus\DbToolsBundle\Attribute;

/**
 * Service tag to autoconfigure anonymizers.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsAnonymizer
{
    public function __construct(
        public string $name,
    ) { }
}
