<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer\PgSQL;

use MakinaCorpus\DbToolsBundle\Restorer\AbstractRestorer;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Restorer extends AbstractRestorer
{
    private ?Process $process = null;

    /**
     * {@inheritdoc}
     */
    public function startRestore(): AbstractRestorer
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
        $args[] = '--clean';
        $args[] = '-d';
        $args[] = $dbParams['dbname'];
        $args[] = '-j';
        $args[] = 2;
        $args[] = '--if-exists';
        $args[] = '--disable-triggers';
        $args[] = $this->backupFilename;

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
