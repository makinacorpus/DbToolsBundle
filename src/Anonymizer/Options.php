<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

class Options
{
    public function __construct(
        private array $options,
    ) {}

    public function has(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function get(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function all(): array
    {
        return $this->options;
    }

    public function count(): int
    {
        return \count($this->options);
    }
}
