<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Helper\Process;

use MakinaCorpus\DbToolsBundle\Helper\Output\OutputInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Process\Process;

trait ProcessTrait
{
    use LoggerAwareTrait;

    protected ?Process $process = null;
    protected ?OutputInterface $output = null;

    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;

        return $this;
    }

    public function execute(?float $timeout = null): static
    {
        $command = $this->buildCommandLine();

        $this->process = Process::fromShellCommandline($command->toString());
        $this->beforeProcess();
        if (null !== $timeout) {
            $this->process->setTimeout($timeout);
        }

        try {
            $this->process->mustRun($this->handleProcessOutput(...));
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
     * Handle process output.
     */
    protected function handleProcessOutput(string $type, string $output): void
    {
        $this->output?->write($output);
        $this->logger?->info(\rtrim($output, \PHP_EOL));
    }

    /**
     * Build the command line to run.
     */
    abstract public function buildCommandLine(): CommandLine;
}
