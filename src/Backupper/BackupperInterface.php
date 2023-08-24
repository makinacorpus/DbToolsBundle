<?php


namespace MakinaCorpus\DbToolsBundle\Backupper;

/**
 * Create backup into the given destination.
 *
 * If no destination is given, creates the backup in system temp directory.
 */
interface BackupperInterface extends \IteratorAggregate
{
    /**
     * Check that backup utility can be execute correctly.
     */
    public function checkBinary(): string;

    public function setDestination(string $destination): self;
    public function getDestination(): ?string;

    public function setVerbose(bool $verbose): self;
    public function isVerbose(): bool;

    public function setExcludedTables(array $tables);
    public function getExcludedTables(): array;

    public function startBackup(): self;

    /**
     * Throw Exception if backup is not successful.
     *
     * @throws \Exception
     */
    public function checkSuccessful(): void;

    public function getExtension(): string;

    public function getOutput(): string;
}