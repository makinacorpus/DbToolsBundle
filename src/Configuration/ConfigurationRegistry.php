<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Configuration;

class ConfigurationRegistry
{
    public function __construct(
        private ?Configuration $default = null,
        /** @var array<string,Configuration> */
        private array $connections = [],
        private ?string $defaultConnection = null,
    ) {}

    public function getDefaultConfig(): Configuration
    {
        return $this->default ??= new Configuration();
    }

    public function getDefaultConnection(): ?string
    {
        if ($this->defaultConnection) {
            return $this->defaultConnection;
        }

        foreach (\array_keys($this->connections) as $name) {
            return $name;
        }

        return null;
    }

    /**
     * @return array<string,Configuration>
     */
    public function getConnectionConfigAll(): array
    {
        return $this->connections;
    }

    public function getConnectionConfig(string $name): Configuration
    {
        return $this->connections[$name] ?? $this->getDefaultConfig();
    }
}
