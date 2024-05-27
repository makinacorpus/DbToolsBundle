<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class MysqlBackupper extends AbstractBackupper
{
    #[\Override]
    public function buildCommandLine(): CommandLine
    {
        $command = new CommandLine($this->binary);

        if ($host = $this->databaseDsn->getHost()) {
            $command->addArg('-h', $host);
        }
        if ($user = $this->databaseDsn->getUser()) {
            $command->addArg('-u', $user);
        }
        if ($port = $this->databaseDsn->getPort()) {
            $command->addArg('-P', $port);
        }
        if ($password = $this->databaseDsn->getPassword()) {
            $command->addArg('-p' . $password);
        }

        foreach ($this->excludedTables as $table) {
            $command->addArg('--ignore-table', $this->databaseDsn->getDatabase() . '.' . $table);
        }

        if ($this->verbose) {
            $command->addArg('-v');
        }

        $this->addCustomOptions($command);

        if ($this->destination) {
            $command->addArg('-r', $this->destination);
        }

        $command->addArg($this->databaseDsn->getDatabase());

        return $command;
    }

    #[\Override]
    public function getExtension(): string
    {
        return 'sql';
    }

    #[\Override]
    protected function getDefaultBinary(): string
    {
        return 'mysqldump';
    }

    #[\Override]
    protected function getBuiltinDefaultOptions(): string
    {
        return '--no-tablespaces';
    }
}
