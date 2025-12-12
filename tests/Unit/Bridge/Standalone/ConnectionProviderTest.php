<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\DbToolsBundle\Bridge\Standalone\StandaloneDatabaseSessionRegistry;
use MakinaCorpus\DbToolsBundle\Error\ConfigurationException;
use MakinaCorpus\DbToolsBundle\Tests\Mock\ConnectionProviderInvokable;
use MakinaCorpus\DbToolsBundle\Tests\Mock\ConnectionProviderNormal;
use PHPUnit\Framework\TestCase;
use MakinaCorpus\DbToolsBundle\Tests\Mock\ConnectionProviderStatic;

class ConnectionProviderTest extends TestCase
{
    public function testWithConnectionProvider(): void
    {
        $databaseSessionRegistry = new StandaloneDatabaseSessionRegistry([
            'default' => ConnectionProviderNormal::class,
        ]);

        $dsn = $databaseSessionRegistry->getConnectionDsn('default');

        self::assertSame(
            'any-vendor://user3:pass3@host3:2345/db3?opt3=default',
            $dsn->toUrl(),
        );
    }

    public function testWithInvokable(): void
    {
        $databaseSessionRegistry = new StandaloneDatabaseSessionRegistry([
            'default' => ConnectionProviderInvokable::class,
        ]);

        $dsn = $databaseSessionRegistry->getConnectionDsn('default');

        self::assertSame(
            'any-vendor://user1:pass1@host1:2345/db1?opt1=val1',
            $dsn->toUrl(),
        );
    }

    public function testWithStaticMethod(): void
    {
        $databaseSessionRegistry = new StandaloneDatabaseSessionRegistry([
            'default' => ConnectionProviderStatic::class . '::createConnectionDsn',
        ]);

        $dsn = $databaseSessionRegistry->getConnectionDsn('default');

        self::assertSame(
            'any-vendor://user5:pass5@host5:2345/db5?opt5=default',
            $dsn->toUrl(),
        );
    }

    public function testWithFunction(): void
    {
        $databaseSessionRegistry = new StandaloneDatabaseSessionRegistry([
            'default' => '\\MakinaCorpus\\DbToolsBundle\\Tests\\Unit\\Bridge\\Symfony\\DependencyInjection\\connection_provider',
        ]);

        $dsn = $databaseSessionRegistry->getConnectionDsn('default');

        self::assertSame(
            'any-vendor://user2:pass2@host2:2345/db2?opt2=val2',
            $dsn->toUrl(),
        );
    }

    public function testNonRegressionDsn(): void
    {
        $databaseSessionRegistry = new StandaloneDatabaseSessionRegistry([
            'default' => 'any-vendor://user4:pass4@host4:2345/db4?opt4=val4',
        ]);

        $dsn = $databaseSessionRegistry->getConnectionDsn('default');

        self::assertSame(
            'any-vendor://user4:pass4@host4:2345/db4?opt4=val4',
            $dsn->toUrl(),
        );
    }

    public function testErrorClass(): void
    {
        $databaseSessionRegistry = new StandaloneDatabaseSessionRegistry([
            'default' => '\stdClass',
        ]);

        self::expectException(ConfigurationException::class);
        self::expectExceptionMessage("'default': connection provider object must either implement MakinaCorpus\\DbToolsBundle\\Bridge\\Standalone\\ConnectionProvider or the __invoke() method.");
        $databaseSessionRegistry->getConnectionDsn('default');
    }
}

function connection_provider()
{
    return 'vendor://user2:pass2@host2:2345/db2?opt2=val2';
}
