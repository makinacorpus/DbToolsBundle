<?php

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
            '-h',
            $dbParams['host'],
            '-U',
            $dbParams['user'],
            '-p',
            $dbParams['port'],
            '-w',
            '-d',
            $dbParams['dbname'],
            '--no-owner',
            '-j',
            2,
            '--disable-triggers',
            $this->backupFilename,
        ];

        if ($this->verbose) {
            $args[] = '-v';
        }

        $this->process = new Process(
            $args,
            null,
            ['PGPASSWORD' => $dbParams['password']],
            null,
            1800
        );

        $this->connection
            ->executeQuery('DROP SCHEMA public CASCADE;')
        ;
        $this->connection
            ->executeQuery('CREATE SCHEMA public;')
        ;

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
