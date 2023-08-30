<?php

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target\Column;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target\Table;

class Anonymizator //extends \IteratorAggregate
{
    private array $anonymizationConfig = [];

    /** @var AbstractAnonymizer[] */
    private array $anonymizers = [];

    public function __construct(
        private string $connectionName,
        private Connection $connection,
    ) { }

    public function addAnonymization(string $table, string $name, array $config): self
    {
        if (!\class_exists($config['anonymiser'])) {
            throw new \InvalidArgumentException(\sprintf(
                'Can not find class "%s", check your configuration.',
                $config['anonymiser']
            ));
        }

        if (!\is_subclass_of($config['anonymiser'], AbstractAnonymizer::class)) {
            throw new \InvalidArgumentException(\sprintf(
                '"%s" is not a "%s", check your configuration.',
                $config['anonymiser'],
                AbstractAnonymizer::class
            ));
        }

        $target = match($config['target']) {
            'table' => new Table($table),
            'column' => new Column($table, $name),
            default => throw new \InvalidArgumentException(\sprintf('Unknown "%s" target, available options are : table, column', $name)),
        };

        if (!isset($this->anonymizationConfig[$table])) {
            $this->anonymizationConfig[$table] = [];
        }
        $this->anonymizationConfig[$table][$name] = [
            'class' => $config['anonymiser'],
            'target' => $target,
            'options' => new Options($config['options']),
        ];

        if (!isset($this->anonymizers[$config['anonymiser']])) {
            $this->anonymizers[$config['anonymiser']] = new $config['anonymiser']($this->connection);
        }

        return $this;
    }

    /**
     * Initialize all anonymizers.
     */
    public function initialize(): self
    {
        foreach($this->anonymizers as $anonymizer) {
            $anonymizer->initialize();
        }

        return $this;
    }

    public function count(): int
    {
        return \count($this->anonymizationConfig);
    }

    /**
     * Anonymize database
     */
    public function anonymize(?array $excludedTables = null): \Generator
    {
        $platfrom = $this->connection->getDatabasePlatform();

        foreach ($this->anonymizationConfig as $table => $tableConfig) {
            if ($excludedTables && \in_array($table, $excludedTables)) {
                continue;
            }

            yield $table => \array_keys($tableConfig);

            $updateQuery = $this->connection
                ->createQueryBuilder()
                ->update($platfrom->quoteIdentifier($table))
            ;

            foreach ($tableConfig as $config) {
                $this->anonymizers[$config['class']]->anonymize(
                    $updateQuery,
                    $config['target'],
                    $config['options']
                );
            }

            $updateQuery->executeQuery();
        }
    }

    /**
     * Clean all anonymizers
     */
    public function clean(): self
    {
        foreach($this->anonymizers as $anonymizer) {
            $anonymizer->clean();
        }

        return $this;
    }

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }
}