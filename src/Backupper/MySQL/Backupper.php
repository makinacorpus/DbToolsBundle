<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper\MySQL;

use MakinaCorpus\DbToolsBundle\Backupper\AbstractBackupper;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Backupper extends AbstractBackupper
{
    private ?Process $process = null;

    /**
     * {@inheritdoc}
     */
    public function startBackup(): self
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

        $args[] = '-r';
        $args[] = $this->destination;
        $args[] = $dbParams['dbname'];
        $args[] = '--no-tablespaces';

        foreach ($this->excludedTables as $table) {
            $args[] = '--ignore-table';
            $args[] = $dbParams['dbname'] . '.' . $table;
        }

        if ($this->verbose) {
            $args[] = '-v';
        }

        $this->process = new Process(
            $args,
            null,
            null,
            null,
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
