<?php

namespace MakinaCorpus\DbToolsBundle\Restorer\MySQL;

use MakinaCorpus\DbToolsBundle\Restorer\AbstractRestorer;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\InputStream;
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
            '-h',
            $dbParams['host'],
            '-u',
            $dbParams['user'],
            '-P',
            $dbParams['port'],
            '-p' . $dbParams['password'],
            $dbParams['dbname'],
        ];

        if ($this->verbose) {
            $args[] = '-v';
        }

        $this->backupStream = \fopen($this->backupFilename, 'r');

        $this->process = new Process(
            $args,
            null,
            null,
            $this->backupStream,
            1800
        );

        // $this->process->setInput($this->backupFilename);

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
