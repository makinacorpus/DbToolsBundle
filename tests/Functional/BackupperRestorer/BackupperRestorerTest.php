<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\BackupperRestorer;

use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use MakinaCorpus\DbToolsBundle\Bridge\Standalone\Bootstrap;
use MakinaCorpus\DbToolsBundle\Configuration\Configuration;
use MakinaCorpus\DbToolsBundle\Configuration\ConfigurationRegistry;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;
use MakinaCorpus\QueryBuilder\Vendor;

/**
 * This class will successively test Backupper and Restorer.
 *
 * 1. Create a table and put data in it
 * 2. Perform Backup
 * 3. Modify data and structure in the previously created table
 * 4. Restore the dump from step 2
 * 5. Check current table's state is equal to step 1 table's state
 */
class BackupperRestorerTest extends FunctionalTestCase
{
    protected array $initialData = [
        [
            'id' => '1',
            'data' => "'string 1'",
        ],
        [
            'id' => '2',
            'data' => "'string 2'",
        ],
        [
            'id' => '3',
            'data' => "'string 3'",
        ],
        [
            'id' => '4',
            'data' => "'string 4'",
        ],
        [
            'id' => '5',
            'data' => "'string 5'",
        ],
        [
            'id' => '6',
            'data' => "'string 6'",
        ],
    ];

    /** @before */
    protected function createTestData(): void
    {
        $this->createOrReplaceTable(
            'table_in_backup_1',
            [
                'id' => 'integer',
                'data' => 'string',
            ],
            $this->initialData,
        );
        $this->createOrReplaceTable(
            'table_in_backup_2',
            [
                'id' => 'integer',
                'data' => 'string',
            ],
            $this->initialData,
        );
    }

    private function createConfigurationRegistry(): ConfigurationRegistry
    {
        // We require ENV vars to be read, so we need this.
        $config = Bootstrap::configGetEnv([]);

        $default = new Configuration(
            backupBinary: $config['backup_binary'] ?? null,
            backupExcludedTables: $config['backup_excluded_tables'] ?? null,
            backupExpirationAge: $config['backup_expiration_age'] ?? null,
            backupOptions: $config['backup_options'] ?? null,
            backupTimeout: $config['backup_timeout'] ?? null,
            restoreBinary: $config['restore_binary'] ?? null,
            restoreOptions: $config['restore_options'] ?? null,
            restoreTimeout: $config['restore_timeout'] ?? null,
            storageDirectory: $config['storage_directory'] ?? null,
            storageFilenameStrategy: $config['storage_filename_strategy'] ?? null,
        );

        return new ConfigurationRegistry($default);
    }

    private function getBackupperFactory(): BackupperFactory
    {
        return new BackupperFactory($this->getDatabaseSessionRegistry(), $this->createConfigurationRegistry());
    }

    private function getRestorerFactory(): RestorerFactory
    {
        return new RestorerFactory($this->getDatabaseSessionRegistry(), $this->createConfigurationRegistry());
    }

    public function testBackupper(): void
    {
        try {
            $backupper = $this->getBackupperFactory()->create();
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $backupper->checkBinary();
        $backupFilename = $this->prepareBackupFilename($backupper->getExtension());

        $backupper
            ->setDestination($backupFilename)
            ->setVerbose(true)
            ->execute()
        ;

        self::assertFileExists($backupFilename);
    }

    /**
     * @depends testBackupper
     */
    public function testRestorer(): void
    {
        // First we do some modifications to the database
        $this->dropTableIfExist('table_in_backup_2');

        $databaseSession = $this->getDatabaseSession();

        $databaseSession
            ->delete('table_in_backup_1')
            ->where('id', 1)
            ->executeStatement()
        ;

        $this->assertSame(5, (int) $databaseSession->executeQuery('select count(*) from table_in_backup_1')->fetchOne());

        $restorerFactory = $this->getRestorerFactory();

        try {
            $restorer = $restorerFactory->create();
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $restorer->checkBinary();

        $databaseSession->close();

        $restorer
            ->setBackupFilename($this->prepareBackupFilename($restorer->getExtension()))
            ->setVerbose(true)
            ->execute()
        ;

        // Now we check data integrity:
        // - All data from initial insert (see self::createTestData) should be there
        // - Deleted data from our previous modifications should not be

        $schemaManager = $databaseSession->getSchemaManager();
        self::assertTrue($schemaManager->tableExists('table_in_backup_1'));
        self::assertTrue($schemaManager->tableExists('table_in_backup_2'));

        $this->assertSame(6, (int) $databaseSession->executeQuery('select count(*) from table_in_backup_1')->fetchOne());
    }

    /**
     * @depends testRestorer
     */
    public function testBackupperWithExtraOptions(): void
    {
        $backupperFactory = $this->getBackupperFactory();

        try {
            $backupper = $backupperFactory->create();
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $backupper->checkBinary();
        $backupFilename = $this->prepareBackupFilename($backupper->getExtension());

        $backupper
            ->setDestination($backupFilename)
            ->setExtraOptions(
                // --skip-ssl is added due to local docker testing environment. Do NOT remove this option.
                match ($this->getDatabaseSession()->getVendorName()) {
                    Vendor::MARIADB => '-v --no-tablespaces --skip-ssl --add-drop-table --skip-quote-names',
                    Vendor::MYSQL => '-v --no-tablespaces --skip-ssl --add-drop-table --skip-quote-names',
                    Vendor::POSTGRESQL => '-v --no-owner -Z 5 --lock-wait-timeout=120',
                    Vendor::SQLITE => '-bail -readonly', // No interesting options for SQLite.
                    default => $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER')),
                },
            )
            ->ignoreDefaultOptions()
            ->setVerbose(false) // Enable via an extra option.
            ->execute()
        ;

        self::assertFileExists($backupFilename);
    }

    /**
     * @depends testBackupperWithExtraOptions
     */
    public function testRestorerWithExtraOptions(): void
    {
        $databaseSession = $this->getDatabaseSession();

        // First we do some modifications to the database
        $this->dropTableIfExist('table_in_backup_2');

        $databaseSession
            ->delete('table_in_backup_1')
            ->where('id', 1)
            ->executeStatement()
        ;

        $this->assertSame(5, (int) $databaseSession->executeQuery('select count(*) from table_in_backup_1')->fetchOne());

        $restorerFactory = $this->getRestorerFactory();

        try {
            $restorer = $restorerFactory->create();
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $restorer->checkBinary();
        $databaseSession->close();

        $restorer
            ->setBackupFilename($this->prepareBackupFilename($restorer->getExtension()))
            ->setExtraOptions(
                // --skip-ssl is added due to local docker testing environment. Do NOT remove this option.
                match ($databaseSession->getVendorName()) {
                    Vendor::MARIADB => '-v --skip-ssl --no-auto-rehash --skip-progress-reports',
                    Vendor::MYSQL => '-v --skip-ssl --no-auto-rehash',
                    Vendor::POSTGRESQL => '-v -j 2 --disable-triggers --clean --if-exists',
                    Vendor::SQLITE => '-bail', // No interesting options for SQLite.
                    default => $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER')),
                },
            )
            ->ignoreDefaultOptions()
            ->setVerbose(false) // Enable via an extra option.
            ->execute()
        ;

        // Now we check data integrity:
        // - All data from initial insert (see self::createTestData) should be there
        // - Deleted data from our previous modifications should not be

        $schemaManager = $this->getDatabaseSession()->getSchemaManager();
        self::assertTrue($schemaManager->tableExists('table_in_backup_1'));
        self::assertTrue($schemaManager->tableExists('table_in_backup_2'));

        $this->assertSame(6, (int) $databaseSession->executeQuery('select count(*) from table_in_backup_1')->fetchOne());
    }

    /**
     * Unit test, but requires the database session.
     */
    public function testCreateBackupper(): void
    {
        $this->skipIfDatabase(Vendor::SQLSERVER);

        $backupperFactory = new BackupperFactory($this->getDatabaseSessionRegistry(), $this->createConfigurationRegistry());

        $backupper = $backupperFactory->create();

        // $this->assertSame($this->getDatabaseSession()->getVendorName(), $this->getPropertyValue($backupper, 'binary'));
        $this->assertNull($this->getPropertyValue($backupper, 'extraOptions'));
        $this->assertIsArray($this->getPropertyValue($backupper, 'excludedTables'));
        $this->assertEmpty($this->getPropertyValue($backupper, 'excludedTables'));
    }

    /**
     * Unit test, but requires the database session.
     */
    public function testCreateAnonymizerWithDefaultOptions(): void
    {
        $this->skipIfDatabase(Vendor::SQLSERVER);

        $backupperFactory = new BackupperFactory(
            $this->getDatabaseSessionRegistry(),
            new ConfigurationRegistry(
                connections: [
                    'default' => new Configuration(
                        backupOptions: '--fake-opt --mock-opt',
                    ),
                    'another' => new Configuration(
                        backupOptions: '-x -y -z',
                    ),
                ]
            ),
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

    /**
     * Unit test, but requires the database session.
     */
    public function testCreateAnonymizerWithExcludedTables(): void
    {
        $this->skipIfDatabase(Vendor::SQLSERVER);

        $backupperFactory = new BackupperFactory(
            $this->getDatabaseSessionRegistry(),
            new ConfigurationRegistry(
                connections: [
                    'default' => new Configuration(
                        backupExcludedTables: ['table1', 'table2'],
                    ),
                    'another' => new Configuration(
                        backupExcludedTables: ['table3', 'table4'],
                    ),
                ]
            ),
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

    /**
     * Prepare file system for database backup.
     */
    private function prepareBackupFilename(string $extension): string
    {
        $dir = \sprintf('/tmp/%s', \getenv('DBAL_DRIVER'));

        if (!\is_dir($dir)) {
            \mkdir($dir, 777, true);
        }

        return \sprintf('%s/backup_test.%s', $dir, $extension);
    }
}
