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
            throw new \InvalidArgumentException(\sprintf("Option '%s' is required", $name));
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

        if (null === $value) {
            return $value;
        }

        if (\is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(\sprintf("Option '%s' must be a string", $name));
    }

    public function getBool(string $name, bool $default = null, bool $required = false): ?bool
    {
        $value = $this->get($name, $default, $required);

        if (null === $value) {
            return $value;
        }

        if (\is_string($value)) {
            return !\in_array(
                \strtolower($value),
                ['0', 'no', 'n', 'false', 'f', '']
            );
        }

        if (!\is_scalar($value)) {
            throw new \InvalidArgumentException(\sprintf("Option '%s' must be a scalar", $name));
        }

        return (bool) $value;
    }

    public function getInt(string $name, int $default = null, bool $required = false): ?int
    {
        $value = $this->get($name, $default, $required);

        if (null === $value) {
            return $value;
        }

        if (\is_numeric($value)) {
            return (int) $value;
        }

        throw new \InvalidArgumentException(\sprintf("Option '%s' must be an int", $name));
    }

    public function getFloat(string $name, float $default = null, bool $required = false): ?float
    {
        $value = $this->get($name, $default, $required);

        if (null === $value) {
            return $value;
        }

        if (\is_numeric($value)) {
            return (float) $value;
        }

        throw new \InvalidArgumentException(\sprintf("Option '%s' must be a float", $name));

    }

    public function getDate(string $name, \DateTimeImmutable $default = null, bool $required = false): ?\DateTimeImmutable
    {
        $value = $this->get($name, $default, $required);

        if (null === $value) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(\sprintf("Option '%s' could not be converted to DateTimeImmutable", $name));
        }
    }

    public function getInterval(string $name, \DateInterval $default = null, bool $required = false): ?\DateInterval
    {
        $value = $this->get($name, $default, $required);

        if (null === $value) {
            return $value;
        }

        if ($value instanceof \DateInterval) {
            return $value;
        }

        try {
            return new \DateInterval($value);
        } catch (\Throwable $e) {
        }

        // Adding a try catch here beacause from PHP8.3, using \DateInterval::createFromDateString
        // with an unvalid value leads to an exception.
        try {
            if ($value = @\DateInterval::createFromDateString($value)) {
                return $value;
            }
        } catch (\Throwable $e) {
        }

        throw new \InvalidArgumentException(\sprintf("Option '%s' could not be converted to DateInterval", $name));
    }
}
