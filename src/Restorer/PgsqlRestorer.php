<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class PgsqlRestorer extends AbstractRestorer
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

        if ($this->verbose) {
            $command->addArg('-v');
        }

        $this->addCustomOptions($command);
        $command->addArg('-d', $this->databaseDsn->getDatabase());
        $command->addArg($this->backupFilename);

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
        return 'pg_restore';
    }

    #[\Override]
    protected function getBuiltinDefaultOptions(): string
    {
        return '-j 2 --clean --if-exists --disable-triggers';
    }
}
