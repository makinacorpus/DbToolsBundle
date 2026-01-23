<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Mock;

class ConnectionProviderInvokable
{
    public function __invoke()
    {
        return 'vendor://user1:pass1@host1:2345/db1?opt1=val1';
    }
}
