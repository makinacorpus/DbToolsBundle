<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class SqliteRestorer extends AbstractRestorer
{
    #[\Override]
    public function buildCommandLine(): CommandLine
    {
        if (!\file_exists($this->backupFilename)) {
            throw new \Exception(\sprintf('Backup file not found (%s)', $this->backupFilename));
        }

        $filename = $this->databaseDsn->getFilename();
        // Remove existing database to restore file in an empty one.
        if (\file_exists($filename)) {
            if (!@\unlink($filename)) {
                throw new \Exception(\sprintf('Database file could not be deleted (%s)', $filename));
            }
        }

        $command = new CommandLine($this->binary);
        $this->addCustomOptions($command);
        $command->addArg($filename);
        $command->addRaw('<');
        $command->addArg($this->backupFilename);

        return $command;
    }

    #[\Override]
    protected function afterProcess(): void
    {
        // We don't need to re-open connection, doctrine/dbal connection does
        // reconnect lazily when queries are submitted.
        $this->databaseSession->close();
    }

    #[\Override]
    protected function getDefaultBinary(): string
    {
        return 'sqlite3';
    }

    #[\Override]
    public function getExtension(): string
    {
        return 'sql';
    }
}
