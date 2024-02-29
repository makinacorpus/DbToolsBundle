<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Process;

use MakinaCorpus\DbToolsBundle\Helper\Log\ChainLoggerAwareTrait;
use Symfony\Component\Process\Process;

trait ProcessTrait
{
    use ChainLoggerAwareTrait;

    protected ?Process $process = null;

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
            $this->getChainLogger()->error($output);
        } else {
            $this->getChainLogger()->info($output);
        }
    }

    /**
     * Build the command line to run.
     */
    abstract public function buildCommandLine(): CommandLine;
}
