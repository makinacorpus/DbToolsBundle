<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

class AnonymizatorRegistry
{
    private array $anonymizators = [];

    public function __construct() {}

    public function addAnonymizator(Anonymizator $anonymizator): self
    {
        $this->anonymizators[$anonymizator->getConnectionName()] = $anonymizator;

        return $this;
    }

    public function get(string $connection): Anonymizator
    {
        if (!isset($this->anonymizators[$connection])) {
            throw new \InvalidArgumentException(\sprintf("Can't find Anonymizator for connection : %s, check your configuration.", $connection));
        }

        return $this->anonymizators[$connection];
    }

    /**
     * @return array<string, Anonymizator>
     */
    public function all(): array
    {
        return $this->anonymizators;
    }
}
