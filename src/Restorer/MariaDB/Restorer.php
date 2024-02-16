<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer\MariaDB;

use MakinaCorpus\DbToolsBundle\Restorer\AbstractRestorer;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;
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
        $command = new CommandLine($this->binary);

        if (isset($dbParams['host'])) {
            $command->addArg('-h', $dbParams['host']);
        }
        if (isset($dbParams['user'])) {
            $command->addArg('-u', $dbParams['user']);
        }
        if (isset($dbParams['port'])) {
            $command->addArg('-P', $dbParams['port']);
        }
        if (isset($dbParams['password'])) {
            $command->addArg('-p' . $dbParams['password']);
        }
        if ($this->verbose) {
            $command->addArg('-v');
        }
        if ($this->extraOptions) {
            $command->addRaw($this->extraOptions);
        }

        $command->addArg($dbParams['dbname']);

        $this->backupStream = \fopen($this->backupFilename, 'r');
        if (false === $this->backupStream) {
            throw new \InvalidArgumentException(\sprintf(
                "Backup file '%s' can't be read",
                $this->backupFilename
            ));
        }

        $this->process = Process::fromShellCommandline($command->toString());
        $this->process->setInput($this->backupStream);
        $this->process->setTimeout(1800);
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
