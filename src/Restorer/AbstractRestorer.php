<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Restorer;

use MakinaCorpus\DbToolsBundle\Configuration\Configuration;
use MakinaCorpus\DbToolsBundle\Helper\Output\NullOutput;
use MakinaCorpus\DbToolsBundle\Helper\Process\CommandLine;
use MakinaCorpus\DbToolsBundle\Helper\Process\ProcessTrait;
use MakinaCorpus\QueryBuilder\DatabaseSession;
use MakinaCorpus\QueryBuilder\Dsn;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

/**
 * Restore a backup file.
 */
abstract class AbstractRestorer implements LoggerAwareInterface
{
    use ProcessTrait;

    protected string $binary;
    protected ?string $backupFilename = null;
    protected string $defaultOptions = '';
    protected ?string $extraOptions = null;
    protected bool $ignoreDefaultOptions = false;
    protected bool $verbose = false;
    protected ?int $timeout = null;

    public function __construct(
        protected readonly DatabaseSession $databaseSession,
        protected readonly Dsn $databaseDsn,
        protected readonly Configuration $config,
    ) {
        $this->binary = $config->getRestoreBinary() ?? $this->getDefaultBinary();
        $this->defaultOptions = $config->getRestoreOptions() ?? $this->getBuiltinDefaultOptions();
        $this->logger = new NullLogger();
        $this->output = new NullOutput();
        $this->timeout = $config->getRestoreTimeout();
    }

    /**
     * Check that restore utility can be executed correctly.
     */
    public function checkBinary(): string
    {
        $process = new Process([$this->binary, '--version']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(\sprintf(
                'Error while running "%s" command, check configuration for binary "%s".',
                $process->getCommandLine(),
                $this->binary,
            ));
        }

        return $process->getOutput();
    }

    public function setBackupFilename(string $filename): self
    {
        $this->backupFilename = $filename;

        return $this;
    }

    public function getBackupFilename(): ?string
    {
        return $this->backupFilename;
    }

    public function setExtraOptions(?string $options): self
    {
        $this->extraOptions = $options;

        return $this;
    }

    public function getExtraOptions(?string $options): ?string
    {
        return $this->extraOptions;
    }

    public function ignoreDefaultOptions(bool $switch = true): self
    {
        $this->ignoreDefaultOptions = $switch;

        return $this;
    }

    public function areDefaultOptionsIgnored(): bool
    {
        return $this->ignoreDefaultOptions;
    }

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;

        return $this;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function setTimeout(?int $timeout): self
    {
        if ($timeout < 0) {
            throw new \InvalidArgumentException("Timeout value must be a postive integer or null.");
        }

        // Accept 0 value as being null (no timeout).
        $this->timeout = 0 === $timeout ? null : $timeout;

        return $this;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    protected function beforeProcess(): void
    {
        $this->process->setTimeout(null === $this->timeout ? null : (float) $this->timeout);
    }

    /**
     * Get default binary path and name (e.g. "/usr/bin/foosql-restore").
     */
    abstract protected function getDefaultBinary(): string;

    /**
     * Provide the built-in default options that will be used if none is given
     * through the dedicated constructor argument.
     */
    protected function getBuiltinDefaultOptions(): string
    {
        return '';
    }

    /**
     * Add default (if not ignored) and extra options to the given command line.
     */
    final protected function addCustomOptions(CommandLine $command): void
    {
        if (!$this->ignoreDefaultOptions) {
            $command->addRaw($this->defaultOptions);
        }
        if ($this->extraOptions) {
            $command->addRaw($this->extraOptions);
        }
    }

    abstract public function getExtension(): string;
}
