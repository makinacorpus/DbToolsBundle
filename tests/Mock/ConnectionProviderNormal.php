<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Mock;

use MakinaCorpus\DbToolsBundle\Bridge\Standalone\ConnectionProvider;

class ConnectionProviderNormal implements ConnectionProvider
{
    #[\Override]
    public function createConnectionDsn(string $name): string
    {
        return 'vendor://user3:pass3@host3:2345/db3?opt3=' . $name;
    }
}
