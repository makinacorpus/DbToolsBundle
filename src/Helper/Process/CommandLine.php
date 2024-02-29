<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Process;

use Symfony\Component\Process\Process;

class CommandLine
{
    /** @var string[] */
    private array $args = [];

    /**
     * Constructor.
     */
    public function __construct(string|array $arg = [], bool $escape = true)
    {
        if (\is_string($arg)) {
            $arg = [$arg];
        }

        \array_walk($arg, function ($item) use ($escape) {
            if (!(\is_string($item) || \is_int($item) || \is_float($item) || \is_null($item))) {
                throw new \InvalidArgumentException(
                    "Each command line argument must be a string, an integer, a float or null."
                );
            }

            $escape ? $this->addArg($item) : $this->addRaw((string) $item);
        });
    }

    /**
     * Add one or more arguments to the command line. These arguments will be
     * escaped in the final command line.
     */
    public function addArg(string|int|float|null ...$arg): self
    {
        foreach ($arg as $item) {
            $this->args[] = $this->escapeArg((string) $item);
        }

        return $this;
    }

    /**
     * Add a raw string to the command line. This string won't be escaped
     * and will be inserted as such in the final command line.
     */
    public function addRaw(string $raw): self
    {
        if ($raw) {
            $this->args[] = $raw;
        }

        return $this;
    }

    public function toString(): string
    {
        $command = \implode(' ', $this->args);

        if (!$this->osIsWindows()) {
            // exec is mandatory to deal with sending a signal to the process.
            $command = 'exec ' . $command;
        }

        return $command;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Escape a string to be used as a shell argument.
     *
     * The code below has been copied (and slightly modified) from the
     * Symfony Process component released under the MIT licence with the
     * following copyright:
     *
     * @copyright Copyright (c) 2004-present Fabien Potencier
     * @author Fabien Potencier <fabien@symfony.com>
     * @author Romain Neutron <imprec@gmail.com>
     * @link https://github.com/symfony/process
     *
     * @see \Symfony\Component\Process\Process::escapeArgument()
     */
    private function escapeArg(?string $argument): string
    {
        if ('' === $argument || null === $argument) {
            return '""';
        }
        if (!$this->osIsWindows()) {
            return "'" . \str_replace("'", "'\\''", $argument) . "'";
        }
        if (\str_contains($argument, "\0")) {
            $argument = \str_replace("\0", '?', $argument);
        }
        if (!\preg_match('/[\/()%!^"<>&|\s]/', $argument)) {
            return $argument;
        }
        $argument = \preg_replace('/(\\\\+)$/', '$1$1', $argument);

        return '"' . \str_replace(['"', '^', '%', '!', "\n"], ['""', '"^^"', '"^%"', '"^!"', '!LF!'], $argument) . '"';
    }

    private function osIsWindows(): bool
    {
        return '\\' === \DIRECTORY_SEPARATOR;
    }
}
