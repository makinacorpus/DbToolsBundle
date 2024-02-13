<?php

declare (strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Storage\Mock;

use MakinaCorpus\DbToolsBundle\Storage\AbstractFilenameStrategy;

class TestFilenameStrategy extends AbstractFilenameStrategy
{
    #[\Override]
    public function generateFilename(
        string $connectionName = 'default',
        string $extension = 'sql',
        bool $anonymized = false
    ): string {
        return $connectionName . '.' . $extension;
    }
}
