<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class SqliteRestorer extends AbstractRestorer
{
    /**
     * {@inheritdoc}
     */
    public function buildCommandLine(): CommandLine
    {
        if (!\file_exists($this->backupFilename)) {
            throw new \Exception(\sprintf('Backup file not found (%s)', $this->backupFilename));
        }

        $dbParams = $this->connection->getParams();
        // Remove existing database to restore file in an empty one.
        \unlink($dbParams['path']);

        $command = new CommandLine($this->binary);
        $this->addCustomOptions($command);
        $command->addArg($dbParams['path']);
        $command->addRaw('<');
        $command->addArg($this->backupFilename);

        return $command;
    }

    #[\Override]
    protected function afterProcess(): void
    {
        $this->connection->close();
        $this->connection->connect();
    }

    public function getExtension(): string
    {
        return 'sql';
    }
}
