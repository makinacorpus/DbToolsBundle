<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;

class MysqlRestorer extends AbstractRestorer
{
    private mixed $backupStream = null;

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
            $command->addArg('-u', $dbParams['user']);
        }
        if (isset($dbParams['port'])) {
            $command->addArg('-P', $dbParams['port']);
        }
        if (isset($dbParams['password'])) {
            $command->addArg('-p' . $dbParams['password']);
        }
        if ($this->verbose) {
            $command->addArg('-v');
        }

        $this->addCustomOptions($command);
        $command->addArg($dbParams['dbname']);

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

    public function getExtension(): string
    {
        return 'sql';
    }
}
