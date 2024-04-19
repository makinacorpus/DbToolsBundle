<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Backupper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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

        // Let's vary the pleasures.
        $randomPlatform = [
            MariaDBPlatform::class,
            MySQLPlatform::class,
            PostgreSQLPlatform::class,
            // @todo Temporary disabling SQLite for random test class.
            // SqlitePlatform::class,
            // SQLServer backup and restore is not supported.
            //SQLServerPlatform::class,
        ][\rand(0, 2)];

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
                    // @todo Temporary disabling SQLite for random test class.
                    // SqlitePlatform::class => 'sqlite',
                    // SQLServer backup and restore is not supported.
                    //SQLServerPlatform::class => 'sqlsrv',
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
            ->willReturn([
                'default' => 'doctrine.dbal.default_connection',
                'another' => 'doctrine.dbal.another_connection',
            ])
        ;
        $doctrineRegistry
            ->expects($this->any())
            ->method('getDefaultConnectionName')
            ->willReturn('default')
        ;

        return $doctrineRegistry;
    }

    /**
     * Get the value of a protected or private property from the given object.
     *
     * Internally, it binds a closure to the given object and calls this closure
     * to extract the protected or private property value.
     */
    private function getPropertyValue(object $object, string $property): mixed
    {
        return (fn () => $this->{$property})->call($object);
    }

    public function testCreateAnonymizer(): void
    {
        $connection = $this->createMockConnection();
        $registry = $this->createMockRegistry($connection);

        $platform = $connection->getDatabasePlatform();

        $platformId = match (true) {
            $platform instanceof MariaDBPlatform => 'mariadb',
            $platform instanceof MySQLPlatform => 'mysql',
            $platform instanceof PostgreSQLPlatform => 'postgresql',
            // @todo Temporary disabling SQLite for random test class.
            // $platform instanceof SqlitePlatform => 'sqlite',
            // SQLServer backup and restore is not supported.
            //$platform instanceof SQLServerPlatform => 'sqlsrv',
            default => throw new \LogicException(\sprintf(
                'Unsupported database platform: %s',
                $platform::class
            ))
        };

        $backupperFactory = new BackupperFactory($registry, self::BINARIES);
        $backupper = $backupperFactory->create();

        $this->assertSame(self::BINARIES[$platformId], $this->getPropertyValue($backupper, 'binary'));
        $this->assertNull($this->getPropertyValue($backupper, 'extraOptions'));
        $this->assertIsArray($this->getPropertyValue($backupper, 'excludedTables'));
        $this->assertEmpty($this->getPropertyValue($backupper, 'excludedTables'));
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

        $this->assertSame('--fake-opt --mock-opt', $this->getPropertyValue($backupper, 'defaultOptions'));
        $this->assertNull($this->getPropertyValue($backupper, 'extraOptions'));
        $this->assertIsArray($this->getPropertyValue($backupper, 'excludedTables'));
        $this->assertEmpty($this->getPropertyValue($backupper, 'excludedTables'));

        $backupper = $backupperFactory->create('another');

        $this->assertSame('-x -y -z', $this->getPropertyValue($backupper, 'defaultOptions'));
        $this->assertNull($this->getPropertyValue($backupper, 'extraOptions'));
        $this->assertIsArray($this->getPropertyValue($backupper, 'excludedTables'));
        $this->assertEmpty($this->getPropertyValue($backupper, 'excludedTables'));
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

        $this->assertNull($this->getPropertyValue($backupper, 'extraOptions'));
        $this->assertIsArray($this->getPropertyValue($backupper, 'excludedTables'));
        $this->assertSame(['table1', 'table2'], $this->getPropertyValue($backupper, 'excludedTables'));

        $backupper = $backupperFactory->create('another');

        $this->assertNull($this->getPropertyValue($backupper, 'extraOptions'));
        $this->assertIsArray($this->getPropertyValue($backupper, 'excludedTables'));
        $this->assertSame(['table3', 'table4'], $this->getPropertyValue($backupper, 'excludedTables'));
    }
}
