<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource;

use MakinaCorpus\DbToolsBundle\Error\DatasourceException;

class Context
{
    private array $datasources = [];

    public function __construct(iterable $datasources)
    {
        foreach ($datasources as $datasource) {
            if (!$datasource instanceof Datasource) {
                throw new \InvalidArgumentException(\sprintf("Value is not a '%s' instance.", Datasource::class));
            }
            $this->datasources[$datasource->getName()] = $datasource;
        }
    }

    /**
     * Get a single datasource.
     */
    public function getDatasource(string $name): Datasource
    {
        return $this->datasources[$name] ?? throw new DatasourceException(\sprintf("Datasource '%s' does not exist.", $name));
    }

    /**
     * Does datasource exists.
     */
    public function hasDatasource(string $name): bool
    {
        return \array_key_exists($name, $this->datasources);
    }
}
