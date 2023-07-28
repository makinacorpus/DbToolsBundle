<?php


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

        $this->process = new Process(
            [
                $this->binary,
                '-h',
                $dbParams['host'],
                '-U',
                $dbParams['user'],
                '-p',
                $dbParams['port'],
                '-w',
                '-f',
                $this->destination,
                '-F', // format custom (not sql)
                'c',
                '-v',
                '-Z', // compression level 0-9
                '5',
                '--lock-wait-timeout=120',
                '--exclude-table-data=' . \implode('|', $this->excludedTables),
                '--blobs',
                $dbParams['dbname'],
            ],
            null,
            ['PGPASSWORD' => $dbParams['password']],
            null,
            600
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