<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class PgsqlRestorer extends AbstractRestorer
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

        if ($this->verbose) {
            $command->addArg('-v');
        }

        $this->addCustomOptions($command);
        $command->addArg('-d', $dbParams['dbname']);
        $command->addArg($this->backupFilename);

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
        return '-j 2 --clean --if-exists --disable-triggers';
    }
}
