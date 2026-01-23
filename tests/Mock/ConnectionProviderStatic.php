<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Mock;

class ConnectionProviderStatic
{
    public static function createConnectionDsn(string $name): string
    {
        return 'vendor://user5:pass5@host5:2345/db5?opt5=' . $name;
    }
}
