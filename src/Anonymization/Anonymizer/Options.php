<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

class Options
{
    public function __construct(
        private array $options = [],
    ) {}

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->options);
    }

    public function get(string $name, mixed $default = null, bool $required = false): mixed
    {
        if ($required && (null === $default) && !$this->has($name)) {
            throw new \InvalidArgumentException(\sprintf("Option '%s' value is required", $name));
        }

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

    /**
     * Clone option with additional options.
     */
    public function with(array $options): self
    {
        $ret = clone $this;
        foreach ($options as $key => $value) {
            $ret->options[$key] = $value;
        }

        return $ret;
    }

    public function toDisplayString(): string
    {
        return \implode(', ', \array_map(
            fn ($key, $value) => $key . ': ' . (\is_array($value) ? '[' . \implode(', ', $value) . ']' : $value),
            \array_keys($this->options),
            \array_values($this->options),
        ));
    }

    public function getString(string $name, string $default = null, bool $required = false): ?string
    {
        $value = $this->get($name, $default, $required);
        if ($value === null || is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(\sprintf("Option '%s' value should be a string", $name));
    }

    public function getInt(string $name, int $default = null, bool $required = false): ?int
    {
        $value = $this->get($name, $default, $required);

        if (\is_numeric($value)) {
            return (int) $value;
        }

        throw new \InvalidArgumentException(\sprintf("Option '%s' value should be an int", $name));
    }

    public function getFloat(string $name, float $default = null, bool $required = false): ?float
    {
        $value = $this->get($name, $default, $required);

        if (\is_numeric($value)) {
            return (float) $value;
        }

        throw new \InvalidArgumentException(\sprintf("Option '%s' value should be a float", $name));

    }

    public function getDate(string $name, \DateTimeImmutable $default = null, bool $required = false): ?\DateTimeImmutable
    {
        $value = $this->get($name, $default, $required);

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(\sprintf("Option '%s' value could not be converted to DateTimeImmutable", $name));
        }
    }

    public function getInterval(string $name, \DateInterval $default = null, bool $required = false): ?\DateInterval
    {
        $value = $this->get($name, $default, $required);

        if ($value instanceof \DateInterval) {
            return $value;
        }

        try {
            return new \DateInterval($value);
        } catch (\Exception $e) {}

        if ($value = \DateInterval::createFromDateString($value)) {
            return $value;
        }

        throw new \InvalidArgumentException(\sprintf("Option '%s' value could not be converted to DateInterval", $name));
    }
}
