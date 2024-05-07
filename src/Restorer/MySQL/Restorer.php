<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer\MySQL;

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

        $args = [
            $this->binary,
        ];

        if (isset($dbParams['host'])) {
            $args[] = '-h';
            $args[] = $dbParams['host'];
        }

        if (isset($dbParams['user'])) {
            $args[] = '-u';
            $args[] = $dbParams['user'];
        }

        if (isset($dbParams['port'])) {
            $args[] = '-P';
            $args[] = $dbParams['port'];
        }

        if (isset($dbParams['password'])) {
            $args[] = '-p' . $dbParams['password'];
        }

        $args[] = $dbParams['dbname'];

        if ($this->verbose) {
            $args[] = '-v';
        }

        $this->backupStream = \fopen($this->backupFilename, 'r');

        if (false === $this->backupStream) {
            throw new \InvalidArgumentException(\sprintf(
                "Backup file '%s' can't be read",
                $this->backupFilename
            ));
        }

        $this->process = new Process(
            $args,
            null,
            null,
            $this->backupStream,
            $this->timeout
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
