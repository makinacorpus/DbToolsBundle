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
abstract class AbstractBackupper implements \IteratorAggregate
{
    /** @var string */
    public const DEFAULT_OPTIONS = '';

    protected ?string $destination = null;
    protected array $excludedTables = [];
    protected string $defaultOptions = '';
    protected ?string $extraOptions = null;
    protected bool $ignoreDefaultOptions = false;
    protected bool $verbose = false;

    public function __construct(
        protected string $binary,
        protected Connection $connection,
        ?string $defaultOptions = null,
    ) {
        $this->defaultOptions = $defaultOptions ?? static::DEFAULT_OPTIONS;

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

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    protected function addCustomOptions(CommandLine $command): void
    {
        if (!$this->ignoreDefaultOptions) {
            $command->addRaw($this->defaultOptions);
        }
        if ($this->extraOptions) {
            $command->addRaw($this->extraOptions);
        }
    }

    abstract public function startBackup(): self;

    /**
     * Throw Exception if backup is not successful.
     *
     * @throws \Exception
     */
    abstract public function checkSuccessful(): void;

    abstract public function getExtension(): string;

    abstract public function getOutput(): string;
}
