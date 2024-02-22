<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;
use Symfony\Component\Process\Process;

/**
 * Create backup into the given destination.
 *
 * If no destination is given, creates the backup in system temp directory.
 */
abstract class AbstractBackupper
{
    protected ?string $destination = null;
    protected array $excludedTables = [];
    protected string $defaultOptions = '';
    protected ?string $extraOptions = null;
    protected bool $ignoreDefaultOptions = false;
    protected ?\Closure $outputCallback = null;
    protected bool $verbose = false;
    protected ?Process $process = null;

    public function __construct(
        protected string $binary,
        protected Connection $connection,
        ?string $defaultOptions = null,
    ) {
        $this->defaultOptions = $defaultOptions ?? $this->getBuiltinDefaultOptions();

        $this->destination = \sprintf(
            '%s/db-tools-backup-%s.dump',
            \sys_get_temp_dir(),
            (new \DateTimeImmutable())->format('YmdHis')
        );
    }

    /**
     * Check that backup utility can be executed correctly.
     */
    public function checkBinary(): string
    {
        $process = new Process([$this->binary, '--version']);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \InvalidArgumentException(\sprintf(
                "Error while trying to process '%s', check configuration for binary '%s",
                $process->getCommandLine(),
                $this->binary,
            ));
        }

        return $process->getOutput();
    }

    public function setDestination(string $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setExcludedTables(array $excludedTables): self
    {
        $this->excludedTables = $excludedTables;

        return $this;
    }

    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }

    public function setExtraOptions(?string $options): self
    {
        $this->extraOptions = $options;

        return $this;
    }

    public function getExtraOptions(?string $options): ?string
    {
        return $this->extraOptions;
    }

    public function ignoreDefaultOptions(bool $switch = true): self
    {
        $this->ignoreDefaultOptions = $switch;

        return $this;
    }

    public function areDefaultOptionsIgnored(): bool
    {
        return $this->ignoreDefaultOptions;
    }

    public function setOutputCallback(?callable $callback): self
    {
        $this->outputCallback = $callback(...);

        return $this;
    }

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function backup(): self
    {
        $command = $this->buildCommandLine();

        $this->process = Process::fromShellCommandline($command->toString());
        $this->process->setTimeout(600);
        $this->beforeBackup();

        try {
            $this->process->mustRun($this->outputCallback);
        } finally {
            $this->afterBackup();
        }

        return $this;
    }

    /**
     * Act just before the backup process starts.
     */
    protected function beforeBackup(): void
    {
    }

    /**
     * Act just after the backup process ends.
     */
    protected function afterBackup(): void
    {
    }

    /**
     * Provide the built-in default options that will be used if none is given
     * through the dedicated constructor argument.
     */
    protected function getBuiltinDefaultOptions(): string
    {
        return '';
    }

    /**
     * Add default (if not ignored) and extra options to the given command line.
     */
    final protected function addCustomOptions(CommandLine $command): void
    {
        if (!$this->ignoreDefaultOptions) {
            $command->addRaw($this->defaultOptions);
        }
        if ($this->extraOptions) {
            $command->addRaw($this->extraOptions);
        }
    }

    abstract public function buildCommandLine(): CommandLine;

    abstract public function getExtension(): string;

    abstract public function getOutput(): string;
}
