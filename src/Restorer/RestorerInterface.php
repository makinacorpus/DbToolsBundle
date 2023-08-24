<?php


namespace MakinaCorpus\DbToolsBundle\Restorer;

/**
 * Restore backup a backup file.
 */
interface RestorerInterface extends \IteratorAggregate
{
    /**
     * Check that restore utility can be execute correctly.
     */
    public function checkBinary(): string;

    public function setBackupFilename(string $filename): self;
    public function getBackupFilename(): ?string;

    public function setVerbose(bool $verbose): self;
    public function isVerbose(): bool;

    public function startRestore(): self;

    /**
     * Throw Exception if restore is not successful.
     *
     * @throws \Exception
     */
    public function checkSuccessful(): void;

    public function getExtension(): string;

    public function getOutput(): string;
}