<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper\PgSQL;

use MakinaCorpus\DbToolsBundle\Backupper\AbstractBackupper;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;
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
        $command = new CommandLine($this->binary);

        if (isset($dbParams['host'])) {
            $command->addArg('-h', $dbParams['host']);
        }
        if (isset($dbParams['user'])) {
            $command->addArg('-U', $dbParams['user']);
        }
        if (isset($dbParams['port'])) {
            $command->addArg('-p', $dbParams['port']);
        }

        $command->addArg('-w');

        if ($this->excludedTables) {
            $command->addArg('--exclude-table-data=' . \implode('|', $this->excludedTables));
        }
        if ($this->verbose) {
            $command->addArg('-v');
        }

        $this->addCustomOptions($command);
        // Custom format (not SQL).
        // Forced for now.
        $command->addArg('-F', 'c');

        if ($this->destination) {
            $command->addArg('-f', $this->destination);
        }

        $command->addArg($dbParams['dbname']);

        $this->process = Process::fromShellCommandline($command->toString());
        $this->process->setEnv(['PGPASSWORD' => $dbParams['password'] ?? '']);
        $this->process->setTimeout(600);
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

    #[\Override]
    protected function getBuiltinDefaultOptions(): string
    {
        // -Z: compression level (0-9)
        return '-Z 5 --lock-wait-timeout=120';
    }
}
