<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

class AnonymizationConfig
{
    /** @var array<string, array<string, AnonymizerConfig>> */
    private array $tableConfigs = [];

    public function __construct(
        public readonly string $connectionName = 'default'
    ) {}

    public function add(AnonymizerConfig $config): void
    {
        if (!isset($this->tableConfigs[$config->table])) {
            $this->tableConfigs[$config->table] = [];
        }

        $this->tableConfigs[$config->table][$config->targetName] = $config;
    }

    /**
     * @return array<string, array<string, AnonymizerConfig>>
     */
    public function all(): array
    {
        return $this->tableConfigs;
    }

    /**
     * Retrieve AnonymizerConfigs for a table.
     *
     * @param ?array $filteredTargets if given, only AnonymizerConfigs
     *   for those specifics targets will be returned.
     *
     * @return array<string, AnonymizerConfig>
     */
    public function getTableConfig(string $table, ?array $filteredTargets = null): array
    {
        $config = $this->tableConfigs[$table] ?? throw new \InvalidArgumentException(\sprintf(
            "Table '%s' does not exist in configuration",
            $table
        ));

        if ($filteredTargets) {
            $ret = [];
            foreach ($filteredTargets as $target) {
                $ret[$target] = $config[$target] ?? throw new \InvalidArgumentException(\sprintf(
                    "Target '%s'.'%s' does not exist in configuration",
                    $table,
                    $target
                ));
            }

            return $ret;
        }

        return $config;
    }
    /**
     * Count tables.
     */
    public function count(): int
    {
        return \count($this->tableConfigs);
    }
}
