<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper\PgSQL;

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
            $args[] = '-U';
            $args[] = $dbParams['user'];
        }
        if (isset($dbParams['port'])) {
            $args[] = '-p';
            $args[] = $dbParams['port'];
        }

        $args[] = '-w';
        $args[] = '-f';
        $args[] = $this->destination;
        $args[] = '-F'; // format custom (not sql)
        $args[] = 'c';
        $args[] = '-Z'; // compression level 0-9
        $args[] = '5';
        $args[] = '--lock-wait-timeout=120';
        if ($this->excludedTables) {
            $args[] = '--exclude-table-data=' . \implode('|', $this->excludedTables);
        }
        $args[] = $dbParams['dbname'];

        if ($this->verbose) {
            $args[] = '-v';
        }

        $this->process = new Process(
            $args,
            null,
            ['PGPASSWORD' => $dbParams['password'] ?? ''],
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
        return 'dump';
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
