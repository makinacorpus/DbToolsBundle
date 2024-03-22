<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Backupper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use PHPUnit\Framework\TestCase;

class BackupperFactoryTest extends TestCase
{
    private const BINARIES = [
        'mariadb' => 'mariadb-dump',
        'mysql' => 'mysqldump',
        'postgresql' => 'pg_dump',
        'sqlite' => 'sqlite3',
    ];

    private function createMockConnection(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $expressionBuilder = new ExpressionBuilder($connection);

        $connection
            ->expects($this->any())
            ->method('getExpressionBuilder')
            ->willReturn($expressionBuilder)
        ;

        // Let's vary the pleasures.
        $randomPlatform = [
            MariaDBPlatform::class,
            MySQLPlatform::class,
            PostgreSQLPlatform::class,
            //SQLServerPlatform::class,
            SqlitePlatform::class,
        ][\rand(0, 3)];

        $platform = new $randomPlatform();
        \assert($platform instanceof AbstractPlatform);

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;
        // Trick to satisfy the DoctrineQueryBuilder used internally
        // by the factory.
        $connection
            ->expects($this->any())
            ->method('getParams')
            ->willReturn([
                'serverVersion' => match ($randomPlatform) {
                    MariaDBPlatform::class => 'mariadb',
                    MySQLPlatform::class => 'mysql',
                    PostgreSQLPlatform::class => 'pgsql',
                    //SQLServerPlatform::class => 'sqlsrv',
                    SqlitePlatform::class => 'sqlite',
                },
            ])
        ;

        return $connection;
    }

    private function createMockRegistry(?Connection $connection = null): ManagerRegistry
    {
        $connection ??= $this->createMockConnection();

        $doctrineRegistry = $this->createMock(ManagerRegistry::class);
        $doctrineRegistry
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection)
        ;
        $doctrineRegistry
            ->expects($this->any())
            ->method('getConnectionNames')
            ->willReturn(['default', 'another'])
        ;
        $doctrineRegistry
            ->expects($this->any())
            ->method('getDefaultConnectionName')
            ->willReturn('default')
        ;

        return $doctrineRegistry;
    }

    private function getPlatformId(AbstractPlatform $platform): string
    {
        return match (true) {
            $platform instanceof MariaDBPlatform => 'mariadb',
            $platform instanceof MySQLPlatform => 'mysql',
            $platform instanceof PostgreSQLPlatform => 'postgresql',
            //$platform instanceof SQLServerPlatform => 'sqlsrv',
            $platform instanceof SqlitePlatform => 'sqlite',
            default => throw new \LogicException(\sprintf(
                'Unsupported database platform: %s',
                $platform::class
            ))
        };
    }

    public function testCreateAnonymizer(): void
    {
        $connection = $this->createMockConnection();
        $registry = $this->createMockRegistry($connection);
        $platformId = $this->getPlatformId($connection->getDatabasePlatform());

        $backupperFactory = new BackupperFactory($registry, self::BINARIES);
        $backupper = $backupperFactory->create();
        $reflection = new \ReflectionClass($backupper);

        $this->assertSame(self::BINARIES[$platformId], $reflection->getProperty('binary')->getValue($backupper));
        $this->assertNull($reflection->getProperty('extraOptions')->getValue($backupper));
        $this->assertIsArray($reflection->getProperty('excludedTables')->getValue($backupper));
        $this->assertEmpty($reflection->getProperty('excludedTables')->getValue($backupper));
    }

    public function testCreateAnonymizerWithDefaultOptions(): void
    {
        $connection = $this->createMockConnection();
        $registry = $this->createMockRegistry($connection);

        $backupperFactory = new BackupperFactory(
            $registry,
            self::BINARIES,
            [
                'default' => '--fake-opt --mock-opt',
                'another' => '-x -y -z',
            ]
        );

        $backupper = $backupperFactory->create();
        $reflection = new \ReflectionClass($backupper);

        $this->assertSame('--fake-opt --mock-opt', $reflection->getProperty('defaultOptions')->getValue($backupper));
        $this->assertNull($reflection->getProperty('extraOptions')->getValue($backupper));
        $this->assertIsArray($reflection->getProperty('excludedTables')->getValue($backupper));
        $this->assertEmpty($reflection->getProperty('excludedTables')->getValue($backupper));

        $backupper = $backupperFactory->create('another');
        $reflection = new \ReflectionClass($backupper);

        $this->assertSame('-x -y -z', $reflection->getProperty('defaultOptions')->getValue($backupper));
        $this->assertNull($reflection->getProperty('extraOptions')->getValue($backupper));
        $this->assertIsArray($reflection->getProperty('excludedTables')->getValue($backupper));
        $this->assertEmpty($reflection->getProperty('excludedTables')->getValue($backupper));
    }

    public function testCreateAnonymizerWithExcludedTables(): void
    {
        $connection = $this->createMockConnection();
        $registry = $this->createMockRegistry($connection);

        $backupperFactory = new BackupperFactory(
            $registry,
            self::BINARIES,
            [],
            [
                'default' => ['table1', 'table2'],
                'another' => ['table3', 'table4'],
            ]
        );

        $backupper = $backupperFactory->create();
        $reflection = new \ReflectionClass($backupper);

        $this->assertNull($reflection->getProperty('extraOptions')->getValue($backupper));
        $this->assertIsArray($reflection->getProperty('excludedTables')->getValue($backupper));
        $this->assertSame(['table1', 'table2'], $reflection->getProperty('excludedTables')->getValue($backupper));

        $backupper = $backupperFactory->create('another');
        $reflection = new \ReflectionClass($backupper);

        $this->assertNull($reflection->getProperty('extraOptions')->getValue($backupper));
        $this->assertIsArray($reflection->getProperty('excludedTables')->getValue($backupper));
        $this->assertSame(['table3', 'table4'], $reflection->getProperty('excludedTables')->getValue($backupper));
    }
}
