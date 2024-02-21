<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer\PgSQL;

use MakinaCorpus\DbToolsBundle\Restorer\AbstractRestorer;
use MakinaCorpus\DbToolsBundle\Utility\CommandLine;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class Restorer extends AbstractRestorer
{
    public const DEFAULT_OPTIONS = '-j 2 --clean --if-exists --disable-triggers';

    private ?Process $process = null;

    /**
     * {@inheritdoc}
     */
    public function startRestore(): AbstractRestorer
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

        $this->process = Process::fromShellCommandline($command->toString());
        $this->process->setEnv(['PGPASSWORD' => $dbParams['password'] ?? '']);
        $this->process->setTimeout(1800);
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
        return 'dump';
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getIterator(): \Traversable
    {
        return $this->process;
    }
}
