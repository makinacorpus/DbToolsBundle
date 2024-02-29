<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class PgsqlBackupper extends AbstractBackupper
{
    /**
     * {@inheritdoc}
     */
    public function buildCommandLine(): CommandLine
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

        return $command;
    }

    #[\Override]
    protected function beforeProcess(): void
    {
        parent::beforeProcess();
        $dbParams = $this->connection->getParams();
        $this->process->setEnv(['PGPASSWORD' => $dbParams['password'] ?? '']);
    }

    public function getExtension(): string
    {
        return 'dump';
    }

    #[\Override]
    protected function getBuiltinDefaultOptions(): string
    {
        // -Z: compression level (0-9)
        return '-Z 5 --lock-wait-timeout=120';
    }
}
