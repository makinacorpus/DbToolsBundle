<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper;

use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Where;

class SqliteBackupper extends AbstractBackupper
{
    /**
     * {@inheritdoc}
     */
    public function buildCommandLine(): CommandLine
    {
        $dbParams = $this->connection->getParams();
        $tablesToBackup = \implode(' ', $this->getTablesToBackup());

        // The CommandLine instance below will generate something like:
        // echo 'BEGIN IMMEDIATE;\n.dump table1 table2 ...' | 'sqlite3' -bail > '/path/to/backup.sql'
        $command = new CommandLine();
        $command->addRaw(\sprintf(
            "echo 'BEGIN IMMEDIATE;\n.dump %s' |", $tablesToBackup
        ));
        $command->addArg($this->binary);
        $this->addCustomOptions($command);
        $command->addArg($dbParams['path']);
        $command->addRaw('>');
        $command->addArg($this->destination);

        return $command;
    }

    public function getExtension(): string
    {
        return 'sql';
    }

    #[\Override]
    protected function getBuiltinDefaultOptions(): string
    {
        return '-bail';
    }

    private function getTablesToBackup(): array
    {
        $query = (new DoctrineQueryBuilder($this->connection))
            ->select('sqlite_master')
            ->column('name')
            ->where('type', 'table')
            ->where(fn (Where $where) => $where->isNotLike('name', 'sqlite_%'))
        ;

        $tables = [];
        foreach ($query->executeQuery() as $table) {
            if(!\in_array($table['name'], $this->excludedTables)) {
                $tables[] = $table['name'];
            }
        }

        return $tables;
    }
}
