<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Resources\FilenameStrategy;

use MakinaCorpus\DbToolsBundle\Storage\AbstractFilenameStrategy;

class CustomFilenameStrategy extends AbstractFilenameStrategy
{
    #[\Override]
    public function generateFilename(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $anonymized = false
    ): string {
        $now = new \DateTimeImmutable();

        return \sprintf(
            '%s/custom%s-%s.%s',
            $connectionName,
            $anonymized ? '-anonymized' : '',
            $now->format('YmdHis'),
            $extension
        );
    }
}
