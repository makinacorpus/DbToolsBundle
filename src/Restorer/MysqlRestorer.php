<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class MysqlRestorer extends AbstractRestorer
{
    private mixed $backupStream = null;

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
        if ($this->verbose) {
            $command->addArg('-v');
        }

        $this->addCustomOptions($command);
        $command->addArg($this->databaseDsn->getDatabase());

        return $command;
    }

    #[\Override]
    protected function beforeProcess(): void
    {
        parent::beforeProcess();

        $this->backupStream = \fopen($this->backupFilename, 'r');

        if (false === $this->backupStream) {
            throw new \InvalidArgumentException(\sprintf(
                "Backup file '%s' can't be read",
                $this->backupFilename
            ));
        }

        $this->process->setInput($this->backupStream);
    }

    #[\Override]
    protected function afterProcess(): void
    {
        \fclose($this->backupStream);
    }

    #[\Override]
    public function getExtension(): string
    {
        return 'sql';
    }
}
