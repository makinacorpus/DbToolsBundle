<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer\SQLite;

use MakinaCorpus\DbToolsBundle\Restorer\AbstractRestorer;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Restorer extends AbstractRestorer
{
    private ?Process $process = null;

    /**
     * {@inheritdoc}
     */
    public function startRestore(): self
    {
        if (!\file_exists($this->backupFilename)) {
            throw new \Exception(\sprintf('Backup file not found (%s)', $this->backupFilename));
        }

        $dbParams = $this->connection->getParams();

        // Remove existing database to restore file in an empty one.
        \unlink($dbParams['path']);

        $command = new CommandLine(
            \sprintf(
                '%s %s%s < "%s"',
                $this->binary,
                $this->extraOptions ? $this->extraOptions . ' ' : '',
                $dbParams['path'],
                \addcslashes($this->backupFilename, '\\"')
            ),
            false
        );

        $this->process = Process::fromShellCommandline($command->toString());
        $this->process->setTimeout(1800);
        $this->process->start();

        return $this;
    }

    public function checkSuccessful(): void
    {
        if (!$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }

        $this->connection->close();
        $this->connection->connect();
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
