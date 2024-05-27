<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class PgsqlBackupper extends AbstractBackupper
{
    #[\Override]
    public function buildCommandLine(): CommandLine
    {
        $command = new CommandLine($this->binary);

        if ($host = $this->databaseDsn->getHost()) {
            $command->addArg('-h', $host);
        }
        if ($user = $this->databaseDsn->getUser()) {
            $command->addArg('-U', $user);
        }
        if ($port = $this->databaseDsn->getPort()) {
            $command->addArg('-p', $port);
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

        $command->addArg($this->databaseDsn->getDatabase());

        return $command;
    }

    #[\Override]
    protected function beforeProcess(): void
    {
        parent::beforeProcess();

        $this->process->setEnv(['PGPASSWORD' => $this->databaseDsn->getPassword() ?? '']);
    }

    #[\Override]
    public function getExtension(): string
    {
        return 'dump';
    }

    #[\Override]
    protected function getDefaultBinary(): string
    {
        return 'pg_dump';
    }

    #[\Override]
    protected function getBuiltinDefaultOptions(): string
    {
        // -Z: compression level (0-9)
        return '-Z 5 --lock-wait-timeout=120';
    }
}
