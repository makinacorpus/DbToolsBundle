<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Storage;

class DefaultFilenameStrategy extends AbstractFilenameStrategy
{
    #[\Override]
    public function generateFilename(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $anonymized = false
    ): string {
        $now = new \DateTimeImmutable();

        return \sprintf(
            '%s/%s/%s%s-%s.%s',
            $now->format('Y'),
            $now->format('m'),
            $connectionName,
            $anonymized ? '-anonymized' : '',
            $now->format('YmdHis'),
            $extension
        );
    }
}
