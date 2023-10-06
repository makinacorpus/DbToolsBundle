<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use Doctrine\DBAL\Connection;
use Symfony\Component\Process\Process;

abstract class AbstractRestorer implements RestorerInterface
{
    protected ?string $backupFilename = null;
    protected bool $verbose = false;

    public function __construct(
        protected string $binary,
        protected Connection $connection,
    ) {}

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

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    abstract public function startRestore(): self;

    abstract public function checkSuccessful(): void;

    abstract public function getExtension(): string;

    abstract public function getOutput(): string;
}
