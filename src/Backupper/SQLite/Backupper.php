<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper\SQLite;

use MakinaCorpus\DbToolsBundle\Backupper\AbstractBackupper;
use MakinaCorpus\QueryBuilder\Bridge\Doctrine\DoctrineQueryBuilder;
use MakinaCorpus\QueryBuilder\Where;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Backupper extends AbstractBackupper
{
    private ?Process $process = null;

    /**
     * {@inheritdoc}
     */
    public function startBackup(): self
    {
        $dbParams = $this->connection->getParams();

        $includeTables = \implode(' ', $this->getTablesToBackup());

        $dumpInSqlite = "echo 'BEGIN IMMEDIATE;\n.dump {$includeTables}'";

        $command = sprintf(
            "{$dumpInSqlite} | %s --bail %s",
            $this->binary,
            $dbParams['path']
        );

        $command .= ' > "' . \addcslashes($this->destination, '\\"') . '"';

        $this->process = Process::fromShellCommandline(
            $command,
            null,
            null,
            null,
            600
        );

        $this->process->start();

        return $this;
    }

    public function checkSuccessful(): void
    {
        if (!$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }
    }

    public function getExtension(): string
    {
        return 'sql';
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getIterator(): \Traversable
    {
        return $this->process;
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
