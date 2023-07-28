<?php


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


        $this->process = new Process(
            [
                $this->binary,
                '-h',
                $dbParams['host'],
                '-u',
                $dbParams['user'],
                '-P',
                $dbParams['port'],
                '-p' . $dbParams['password'],
                '-r',
                $this->destination,
                ...\array_map(fn ($item) => '--ignore-table ' . $item, $this->excludedTables),
                $dbParams['dbname'],
                '--no-tablespaces',
            ],
            null,
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