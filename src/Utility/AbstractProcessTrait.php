<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Utility;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

trait AbstractProcessTrait
{
    protected ?Process $process = null;
    private ?ChainLogger $logger = null;

    public function execute(?float $timeout = null): self
    {
        $command = $this->buildCommandLine();

        $this->process = Process::fromShellCommandline($command->toString());
        $this->beforeProcess();
        if (null !== $timeout) {
            $this->process->setTimeout($timeout);
        }

        try {
            $this->process->mustRun($this->logProcessOutput(...));
        } finally {
            $this->afterProcess();
        }

        return $this;
    }

    /**
     * Add a logger to retrieve the process output. It is possible to give
     * many loggers.
     */
    public function addLogger(LoggerInterface $logger): self
    {
        $this->getLogger()->addLogger($logger);

        return $this;
    }

    /**
     * Act just before the process starts.
     */
    protected function beforeProcess(): void
    {
    }

    /**
     * Act just after the process ends.
     */
    protected function afterProcess(): void
    {
    }

    /**
     * Log process output.
     */
    protected function logProcessOutput(string $type, string $output): void
    {
        if (Process::ERR === $type) {
            $this->getLogger()->error($output);
        } else {
            $this->getLogger()->info($output);
        }
    }

    /**
     * Initialize and get the logger.
     */
    protected function getLogger(): ChainLogger
    {
        return $this->logger ??= new ChainLogger();
    }

    /**
     * Build the command line to run.
     */
    abstract public function buildCommandLine(): CommandLine;
}
