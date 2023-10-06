<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

class AnonymizatorRegistry
{
    private array $anonymizator = [];

    public function __construct() {}

    public function addAnonymizator(Anonymizator $anonymizator): self
    {
        $this->anonymizator[$anonymizator->getConnectionName()] = $anonymizator;

        return $this;
    }

    public function get(string $connection): Anonymizator
    {
        if (!isset($this->anonymizator[$connection])) {
            throw new \InvalidArgumentException(\sprintf("Can't find Anonymizator for connection : %s, check your configuration.", $connection));
        }

        return $this->anonymizator[$connection];
    }
}
