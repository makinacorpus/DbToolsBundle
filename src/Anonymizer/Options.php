<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer;

class Options
{
    public function __construct(
        private array $options = [],
    ) {}

    public function has(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function all(): array
    {
        return $this->options;
    }

    public function count(): int
    {
        return \count($this->options);
    }

    public function toDisplayString(): string
    {
        return \implode(', ', \array_map(
            fn ($key, $value) => $key . ': ' . (\is_array($value) ? '[' . \implode(', ', $value) . ']' : $value),
            \array_keys($this->options),
            \array_values($this->options),
        ));
    }
}
