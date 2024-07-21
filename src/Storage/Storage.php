<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Storage;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 *   Do not use class as it may change in future releases.
 */
class Storage
{
    public function __construct(
        private string $storagePath,
        private string $expirationAge,
        private ?array $filenameStrategies = null,
    ) {
        $this->storagePath = \rtrim($storagePath, '/');
    }

    public function listBackups(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $onlyExpired = false,
        string $preserveFile = null
    ): array {
        // In order to avoid listing dumps from other connections, we must
        // filter files using the connection name infix. When a custom strategy
        // is provided, there is no way to reproduce this filtering, it's up to
        // the user to give a restricted folder name.
        $rootDir = $this->getFilenameStrategy($connectionName)->getRootDir($this->storagePath, $connectionName);

        if (!(new Filesystem())->exists($rootDir)) {
            return [];
        }

        $finder = (new Finder())
            ->files()
            ->depth('< 10')
            ->in($rootDir)
            ->name('*.' . $extension)
            ->sortByName()
        ;

        $expirationDate = new \Datetime($this->expirationAge);
        $list = [];

        foreach ($finder as $file) {
            $lastModified = new \Datetime(\date("Y-m-d H:i:s", \filemtime((string) $file)));
            $age = $lastModified->diff(new \DateTimeImmutable(''));

            if (!$onlyExpired || ($lastModified < $expirationDate)) {
                if ($file !== $preserveFile) {
                    $list[] = [$age->format('%a days'), $file];
                }
            }
        }

        return $list;
    }

    public function generateFilename(string $connectionName = 'default', string $extension = 'sql', bool $anonymized = false): string
    {
        $strategy = $this->getFilenameStrategy($connectionName);
        $rootDir = $strategy->getRootDir($this->storagePath, $connectionName);

        $filename = \rtrim($rootDir, '/') . '/' . $strategy->generateFilename($connectionName, $extension, $anonymized);

        (new Filesystem())->mkdir(\dirname($filename));

        return $filename;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    protected function getFilenameStrategy(string $connectionName): FilenameStrategyInterface
    {
        return $this->filenameStrategies[$connectionName] ?? $this->filenameStrategies['default'] ?? new DefaultFilenameStrategy();
    }
}
