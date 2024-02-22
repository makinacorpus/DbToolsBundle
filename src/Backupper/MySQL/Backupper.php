<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper\MySQL;

use MakinaCorpus\DbToolsBundle\Backupper\AbstractBackupper;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;

class Backupper extends AbstractBackupper
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

        return $command;
    }

    public function getExtension(): string
    {
        return 'sql';
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    #[\Override]
    protected function getBuiltinDefaultOptions(): string
    {
        return '--no-tablespaces';
    }
}
