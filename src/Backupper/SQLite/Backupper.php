<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Backupper\SQLite;

use MakinaCorpus\DbToolsBundle\Backupper\AbstractBackupper;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;
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
        $tablesToBackup = \implode(' ', $this->getTablesToBackup());

        $command = new CommandLine(
            \sprintf(
                "echo 'BEGIN IMMEDIATE;\n.dump %s' | %s -bail %s%s  > \"%s\"",
                $tablesToBackup,
                $this->binary,
                $this->extraOptions ? $this->extraOptions . ' ' : '',
                $dbParams['path'],
                \addcslashes($this->destination, '\\"')
            ),
            false
        );

        $this->process = Process::fromShellCommandline($command->toString());
        $this->process->setTimeout(600);
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
