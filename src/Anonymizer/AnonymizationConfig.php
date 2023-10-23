<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

class AnonymizationConfig
{
    /** @var array<string, array<string, AnonymizerConfig>> */
    private array $tableConfigs = [];

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
     * @return array<string, AnonymizerConfig>
     */
    public function getTableConfig($table): array
    {
        return $this->tableConfigs[$table] ?? throw new \InvalidArgumentException(\sprintf(
            "Table '%s' does not exist in configuration",
            $table
        ));
    }

    /**
     * @return array<string, AnonymizerConfig>
     */
    public function getTableConfigTargets(string $table, ?array $targets = null): array
    {
        $config = $this->getTableConfig($table);

        if ($targets) {
            $ret = [];
            foreach ($targets as $target) {
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
