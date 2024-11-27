<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymization\Datasource;

class EnumDatasource extends Datasource
{
    private ?array $data = null;
    private ?string $filename = null;
    private array $expressions = [];

    public function __construct(string $name, string|array $source, ?array $expressions = [])
    {
        parent::__construct($name);

        if (\is_array($source)) {
            $this->data = $source;
        } else {
            $this->filename = $source;
        }

        foreach (\array_values($expressions) as $number => $expression) {
            if (\is_string($expression)) {
                $this->expressions[] = new Expression($expression, $name, $number);
            } else if ($expression instanceof Expression) {
                $this->expressions[] = $expression;
            } else {
                $this->throwError(\sprintf("expression #%d is not a string nor a '%s' instance.", $number, Expression::class));
            }
        }
    }

    #[\Override]
    public function random(Context $context): string|array
    {
        if ($this->expressions) {
            $expression = $this->expressions[\rand(0, \count($this->expressions) - 1)];
            \assert($expression instanceof Expression);

            return $expression->execute($context);
        }

        return $this->rawRandom();
    }

    #[\Override]
    public function iterator(Context $context): iterable
    {
        return (fn () => yield from $this->data)();
    }

    #[\Override]
    public function count(): int
    {
        $this->initialize();

        return \count($this->data);
    }

    /**
     * Get a random item from the data list, without expression handling.
     *
     * @internal
     *   This is being used in unit tests.
     */
    public function rawAt(int $position = 0): string
    {
        $this->initialize();

        return $this->data[$position];
    }

    /**
     * Get a random item from the data list, without expression handling.
     *
     * @internal
     *   This is being used in the Expression class.
     * @see Expression
     */
    public function rawRandom(): string
    {
        $this->initialize();

        return $this->data[\rand(0, \count($this->data) - 1)];
    }

    /**
     * Internal values initialization.
     */
    private function initialize(): void
    {
        if (null !== $this->data) {
            return;
        }

        if (null === $this->filename) {
            $this->throwError("was initialized without data nor filename.");
        }
        if (!\file_exists($this->filename)) {
            $this->throwError(\sprintf("file '%s': does not exist.", $this->filename));
        }

        $this->data = [];

        $ext = ($pos = \strrpos($this->filename, '.')) ? \substr($this->filename, $pos + 1) : 'txt';

        $source = match ($ext) {
            'js', 'json' => $this->parseJsonFile($this->filename),
            'txt' => $this->parseTextFile($this->filename),
            default => $this->throwError(\sprintf("file '%s': unsupported file format '%s'.", $this->filename, $ext)),
        };

        foreach ($source as $line => $item) {
            if (!\is_string($item)) {
                $this->throwError(\sprintf("file '%s': line #%s is not a valid value.", $this->filename, $line));
            }
            if (empty($item)) {
                // @todo log error?
                continue;
            }
            $this->data[] = $item;
        }
    }

    /**
     * Parse data from a JSON file.
     */
    private function parseJsonFile(string $filename): iterable
    {
        $list = \json_decode(\file_get_contents($filename), true);

        if (!\is_array($list)) {
            $this->throwError(\sprintf("file '%s': does not contain valid JSON.", $this->filename));
        }

        return (function () use ($list) {
            $count = 1;
            foreach ($list as $value) {
                if (!\is_string($value)) {
                    $this->throwError(\sprintf("file '%s': item #%s is not a string.", $this->filename, $count));
                }
                yield $count => $value;
                $count++;
            }
        })();
    }

    /**
     * Parse data from a text file.
     */
    private function parseTextFile(string $filename): iterable
    {
        if (!$handle = \fopen($filename, 'r')) {
            $this->throwError(\sprintf("file '%s': could not open file for reading.", $this->filename));
        }

        return (function () use ($handle) {
            try {
                $count = 1;
                while ($value = \fgets($handle)) {
                    yield $count => \trim($value);
                    $count++;
                }
            } finally {
                @\fclose($handle);
            }
        })();
    }
}
