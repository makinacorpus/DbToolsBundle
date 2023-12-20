<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DbToolsStorage
{
    public function __construct(
        private string $storagePath,
        private string $expirationAge,
    ) {
        $this->storagePath = \rtrim($storagePath, '/');
    }

    public function listBackups(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $onlyExpired = false,
        string $preserveFile = null
    ): array {
        $finder = (new Finder())
            ->files()
            ->in($this->storagePath)
            ->name(\sprintf('%s*.%s', $connectionName, $extension))
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
        $filesystem = new Filesystem();
        $now = new \DateTimeImmutable();

        $dir = \sprintf(
            '%s/%s/%s',
            $this->storagePath,
            $now->format('Y'),
            $now->format('m')
        );
        $filesystem->mkdir($dir);

        return \sprintf(
            '%s/%s%s-%s.%s',
            $dir,
            $connectionName,
            $anonymized ? '-anonymized' : '',
            $now->format('YmdHis'),
            $extension
        );
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }
}
