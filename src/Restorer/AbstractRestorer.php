<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use Doctrine\DBAL\Connection;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;
use Symfony\Component\Process\Process;

/**
 * Restore backup a backup file.
 */
abstract class AbstractRestorer implements \IteratorAggregate
{
    protected ?string $backupFilename = null;
    protected string $defaultOptions = '';
    protected ?string $extraOptions = null;
    protected bool $ignoreDefaultOptions = false;
    protected bool $verbose = false;

    public function __construct(
        protected string $binary,
        protected Connection $connection,
        ?string $defaultOptions = null,
    ) {
        $this->defaultOptions = $defaultOptions ?? $this->getBuiltinDefaultOptions();
    }

    /**
     * Check that restore utility can be executed correctly.
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

    public function setBackupFilename(string $filename): self
    {
        $this->backupFilename = $filename;

        return $this;
    }

    public function getBackupFilename(): ?string
    {
        return $this->backupFilename;
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

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
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

    abstract public function startRestore(): self;

    /**
     * Throw Exception if restore is not successful.
     *
     * @throws \Exception
     */
    abstract public function checkSuccessful(): void;

    abstract public function getExtension(): string;

    abstract public function getOutput(): string;
}
