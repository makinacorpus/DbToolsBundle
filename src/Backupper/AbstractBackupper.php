<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use Doctrine\DBAL\Connection;
use Symfony\Component\Process\Process;

/**
 * Create backup into the given destination.
 *
 * If no destination is given, creates the backup in system temp directory.
 */
abstract class AbstractBackupper implements \IteratorAggregate
{
    protected ?string $destination = null;
    protected bool $verbose = false;
    protected array $excludedTables = [];
    protected float $timeout = 600;

    public function __construct(
        protected string $binary,
        protected Connection $connection,
    ) {
        $this->destination = \sprintf(
            '%s/db-tools-backup-%s.dump',
            \sys_get_temp_dir(),
            (new \DateTimeImmutable())->format('YmdHis')
        );
    }

    /**
     * Check that backup utility can be execute correctly.
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

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }

    public function setTimeout(float $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
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
