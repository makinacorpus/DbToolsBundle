<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Standalone;

use MakinaCorpus\DbToolsBundle\Database\DatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;
use MakinaCorpus\QueryBuilder\BridgeFactory;
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Dsn;

/**
 * makinacorpus/query-builder based implementation.
 */
class StandaloneDatabaseSessionRegistry implements DatabaseSessionRegistry
{
    /**
     * @param array $connections
     *   Keys are connection names, values are database URLs.
     */
    public function __construct(
        private array $connections,
        private ?string $defaultConnection = null,
    ) {}

    #[\Override]
    public function getConnectionNames(): array
    {
        return \array_keys($this->connections);
    }

    #[\Override]
    public function getDefaultConnectionName(): string
    {
        if (null === $this->defaultConnection) {
            foreach (\array_keys($this->connections) as $name) {
                return $this->defaultConnection = $name;
            }
            throw new ConfigurationException("No connections were configured.");
        }
        return $this->defaultConnection;
    }

    #[\Override]
    public function getConnectionDsn(string $name): Dsn
    {
        return Dsn::fromString($this->getConnectionUri($name));
    }

    #[\Override]
    public function getDatabaseSession(string $name): DatabaseSession
    {
        return BridgeFactory::create($this->getConnectionUri($name));
    }

    protected function getConnectionUri(string $name): string
    {
        $value = $this->connections[$name] ?? throw new ConfigurationException(\sprintf("'%s': connection does not exist.", $name));

        $callable = null;

        if (\is_callable($value)) {
            $callable = \Closure::fromCallable($value);
        } elseif (\class_exists($value)) {
            $object = new $value();

            if (\is_callable($object)) {
                // Class implements __invoke().
                $callable = $object;
            } else {
                if (!$object instanceof ConnectionProvider) {
                    throw new ConfigurationException(\sprintf("'%s': connection provider object must either implement %s or the __invoke() method.", $name, ConnectionProvider::class));
                }
                $callable = $object->createConnectionDsn(...);
            }
        }

        return $callable ? $callable($name) : $value;
    }
}
