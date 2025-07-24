<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Anonymizer;

/**
 * @todo
 *   Remove "extends Options" in 3.0. Change AbstractAnonymizer::__construct() signature accordingly.
 */
class Context extends Options
{
    public function __construct(
        public Options $options = new Options(),
    ) {}

    public function withOptions(Options $options): Context
    {
        return new self($options);
    }

    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    private function throwOptionsDeprecation(string $method): never
    {
        throw new \LogicException(\sprintf("Calling %s::%s() is forbidden, this method only exists for backward compatibility purpose..", static::class, $method));
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function has(string $name): bool
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function get(string $name, mixed $default = null, bool $required = false): mixed
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function all(): array
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function count(): int
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function with(array $options): Options
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function toDisplayString(): string
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function getString(string $name, ?string $default = null, bool $required = false): ?string
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function getBool(string $name, ?bool $default = null, bool $required = false): ?bool
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function getInt(string $name, ?int $default = null, bool $required = false): ?int
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function getFloat(string $name, ?float $default = null, bool $required = false): ?float
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function getDate(string $name, ?\DateTimeImmutable $default = null, bool $required = false): ?\DateTimeImmutable
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }

    #[\Override]
    #[\Deprecated(message: "Only exists for class signature backward compatibility.", since: "2.1.0")]
    public function getInterval(string $name, ?\DateInterval $default = null, bool $required = false): ?\DateInterval
    {
        $this->throwOptionsDeprecation(__METHOD__);
    }
}
