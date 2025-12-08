<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Attribute;

/**
 * Service tag to autoconfigure anonymizers.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsAnonymizer
{
    public function __construct(
        public string $name,
        public string $pack,
        public ?string $description = null,
        /**
         * @var array<string>
         *   Array of class or interface names required for this anonymizer to work.
         *   Anonymizers whose requirements are not met will not be usable and raise
         *   an error in listing.
         */
        public array $requires = [],
        /**
         * @var array<string>
         *   List of composer dependencies for filling the requirements.
         */
        public ?array $dependencies = [],
    ) {}

    public function id(): string
    {
        // Id is 'pack.name' except for anonymiser from core
        // which don't have prefix.
        return ('core' !== $this->pack ? $this->pack . '.' : '') . $this->name;
    }

    public function missingRequirements(): bool
    {
        foreach ($this->requires as $classOrInterfaceName) {
            if (!\class_exists($classOrInterfaceName) && !\interface_exists($classOrInterfaceName)) {
                return true;
            }
        }
        return false;
    }
}
