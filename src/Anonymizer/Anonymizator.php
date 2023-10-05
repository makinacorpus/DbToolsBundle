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
        private AnonymizerRegistry $anonymizerRegistry
    ) { }

    public function addAnonymization(string $table, string $name, array $config): self
    {
        if (!$anonymizer = $this->anonymizerRegistry->get($config['anonymizer'])) {
            throw new \InvalidArgumentException(\sprintf(
                'Can not find anonymizer "%s", check your anonymization configuration for table "%s", key "%s".',
                $config['anonymizer'],
                $table,
                $name
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
            'anonymizer' => $config['anonymizer'],
            'target' => $target,
            'options' => new Options($config['options']),
        ];

        if (!isset($this->anonymizers[$config['anonymizer']])) {
            $this->anonymizers[$config['anonymizer']] = new $anonymizer($this->connection);
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

            foreach ($tableConfig as $name => $config) {
                if (!isset($config['anonymizer'])) {
                    throw new \InvalidArgumentException(\sprintf('Missing anonymizer "%s" for table "%s", key "%s"', $table, $name));
                }

                $this->anonymizers[$config['anonymizer']]->anonymize(
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