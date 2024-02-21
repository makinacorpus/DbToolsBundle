<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper\MySQL;

use MakinaCorpus\DbToolsBundle\Backupper\AbstractBackupper;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Backupper extends AbstractBackupper
{
    public const DEFAULT_OPTIONS = '--no-tablespaces';

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
            $command->addArg('-u', $dbParams['user']);
        }
        if (isset($dbParams['port'])) {
            $command->addArg('-P', $dbParams['port']);
        }
        if (isset($dbParams['password'])) {
            $command->addArg('-p' . $dbParams['password']);
        }

        foreach ($this->excludedTables as $table) {
            $command->addArg('--ignore-table', $dbParams['dbname'] . '.' . $table);
        }

        if ($this->verbose) {
            $command->addArg('-v');
        }
        $this->addCustomOptions($command);
        if ($this->destination) {
            $command->addArg('-r', $this->destination);
        }

        $command->addArg($dbParams['dbname']);

        $this->process = Process::fromShellCommandline($command->toString());
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
