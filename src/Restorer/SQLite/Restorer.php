<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer\SQLite;

use MakinaCorpus\DbToolsBundle\Restorer\AbstractRestorer;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Restorer extends AbstractRestorer
{
    private ?Process $process = null;
    private mixed $backupStream = null;
    /**
     * {@inheritdoc}
     */
    public function startRestore(): self
    {
        $dbParams = $this->connection->getParams();

        if (!\file_exists($this->backupFilename)) {
            throw new \Exception(\sprintf('Backup file not found (%s)', $this->backupFilename));
        }

        // Remove existing database to restore file in an empty one
        \unlink($dbParams['path']);
        $command = $this->binary . ' ' . $dbParams['path'];
        $command .= ' < "' . \addcslashes($this->backupFilename, '\\"') . '"';
        $this->backupStream = \fopen($this->backupFilename, 'r');

        if (false === $this->backupStream) {
            throw new \InvalidArgumentException(\sprintf(
                "Backup file '%s' can't be read",
                $this->backupFilename
            ));
        }

        $this->process = Process::fromShellCommandline(
            $command,
            null,
            null,
            null,
            1800
        );

        $this->process->start();

        return $this;
    }

    public function checkSuccessful(): void
    {
        if (!$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }

        \fclose($this->backupStream);
    }

    public function getExtension(): string
    {
        return 'sql';
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getIterator(): \Traversable
    {
        return $this->process;
    }
}
