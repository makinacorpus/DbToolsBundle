<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Storage;

use MakinaCorpus\DbToolsBundle\Configuration\ConfigurationRegistry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 *   Do not use class as it may change in future releases.
 */
class Storage
{
    public function __construct(
        private ConfigurationRegistry $configReg,
        private ?array $filenameStrategies = null,
    ) {}

    public function listBackups(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $onlyExpired = false,
        ?string $preserveFile = null
    ): array {
        $config = $this->configReg->getConnectionConfig($connectionName);
        $storagePath = \rtrim($config->getStorageDirectory(), '/');

        // In order to avoid listing dumps from other connections, we must
        // filter files using the connection name infix. When a custom strategy
        // is provided, there is no way to reproduce this filtering, it's up to
        // the user to give a restricted folder name.
        $rootDir = $this->getFilenameStrategy($connectionName)->getRootDir($storagePath, $connectionName);

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

        $expirationDate = new \Datetime($config->getBackupExpirationAge());
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
        $config = $this->configReg->getConnectionConfig($connectionName);
        $storagePath = \rtrim($config->getStorageDirectory(), '/');

        $strategy = $this->getFilenameStrategy($connectionName);
        $rootDir = $strategy->getRootDir($storagePath, $connectionName);

        $filename = \rtrim($rootDir, '/') . '/' . $strategy->generateFilename($connectionName, $extension, $anonymized);

        (new Filesystem())->mkdir(\dirname($filename));

        return $filename;
    }

    public function getStoragePath(): string
    {
        return $this->configReg->getDefaultConfig()->getStorageDirectory();
    }

    protected function getFilenameStrategy(string $connectionName): FilenameStrategyInterface
    {
        return $this->filenameStrategies[$connectionName] ?? $this->filenameStrategies['default'] ?? new DefaultFilenameStrategy();
    }
}
