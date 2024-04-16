<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\BackupperRestorer;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
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

    private function getBackupperFactory(?Connection $connection = null): BackupperFactory
    {
        $connection = $connection ?? $this->getDoctrineConnection();

        $mockDoctrineRegistry = $this->createMock(ManagerRegistry::class);
        $mockDoctrineRegistry
            ->expects($this->atLeast(1))
            ->method('getConnection')
            ->willReturn($connection)
        ;

        return new BackupperFactory($mockDoctrineRegistry, [
            'mariadb' => 'mariadb-dump',
            'mysql' => 'mysqldump',
            'postgresql' => 'pg_dump',
            'sqlite' => 'sqlite3',
        ]);
    }

    private function getRestorerFactory(?Connection $connection = null): RestorerFactory
    {
        $connection = $this->getDoctrineConnection();

        $mockDoctrineRegistry = $this->createMock(ManagerRegistry::class);
        $mockDoctrineRegistry
            ->expects($this->atLeast(1))
            ->method('getConnection')
            ->willReturn($connection)
        ;

        return new RestorerFactory($mockDoctrineRegistry, [
            'mariadb' => 'mariadb',
            'mysql' => 'mysql',
            'postgresql' => 'pg_restore',
            'sqlite' => 'sqlite3',
        ]);
    }

    public function testBackupper(): void
    {
        try {
            $backupper = $this->getBackupperFactory()->create();
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $backupper->checkBinary();
        $backupFilename = $this->prepareAndGetBackupFilename($backupper->getExtension());

        $backupper
            ->setDestination($backupFilename)
            ->setVerbose(true)
            ->execute()
        ;

        self::assertFileExists($backupFilename);
    }

    /**
     * @depends testRestorer
     */
    public function testBackupperWithExtraOptions(): void
    {
        $backupperFactory = $this->getBackupperFactory($this->getDoctrineConnection());

        try {
            $backupper = $backupperFactory->create();
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $backupper->checkBinary();
        $backupFilename = $this->prepareAndGetBackupFilename($backupper->getExtension());

        $backupper
            ->setDestination($backupFilename)
            ->setExtraOptions(match ($this->getDatabaseSession()->getVendorName()) {
                Vendor::MARIADB => '-v --no-tablespaces --add-drop-table --skip-quote-names',
                Vendor::MYSQL => '-v --no-tablespaces --add-drop-table --skip-quote-names',
                Vendor::POSTGRESQL => '-v --no-owner -Z 5 --lock-wait-timeout=120',
                Vendor::SQLITE => '-bail -readonly', // No interesting options for SQLite.
                default => $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER')),
            })
            ->ignoreDefaultOptions()
            ->setVerbose(false) // Enable via an extra option.
            ->execute()
        ;

        self::assertFileExists($backupFilename);
    }

    /**
     * @depends testBackupper
     */
    public function testRestorer(): void
    {
        $connection = $this->getDoctrineConnection();

        // First we do some modifications to the database
        $this->dropTableIfExist('table_in_backup_2');
        $connection
            ->createQueryBuilder()
            ->delete('table_in_backup_1')
            ->where('id = 1')
            ->executeStatement()
        ;

        $restorerFactory = $this->getRestorerFactory($connection);

        try {
            $restorer = $restorerFactory->create();
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $restorer->checkBinary();

        $connection->close();

        $restorer
            ->setBackupFilename($this->prepareAndGetBackupFilename($restorer->getExtension()))
            ->setVerbose(true)
            ->execute()
        ;

        // Now we check data integrity:
        // - All data from initial insert (see self::createTestData) should be there
        // - Deleted data from our previous modifications should not be

        $schemaManager = $this->getDatabaseSession()->getSchemaManager();
        self::assertTrue($schemaManager->tableExists('table_in_backup_1'));
        self::assertTrue($schemaManager->tableExists('table_in_backup_2'));

        $this->assertSame(
            6,
            $this->getDatabaseSession()->executeQuery('select count(*) from table_in_backup_1')->fetchOne(),
        );
    }

    /**
     * @depends testBackupperWithExtraOptions
     */
    public function testRestorerWithExtraOptions(): void
    {
        $session = $this->getDatabaseSession();

        // First we do some modifications to the database
        $this->dropTableIfExist('table_in_backup_2');

        $session
            ->delete('table_in_backup_1')
            ->where('id', 1)
            ->executeStatement()
        ;

        $restorerFactory = $this->getRestorerFactory($this->getDoctrineConnection());

        try {
            $restorer = $restorerFactory->create();
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $restorer->checkBinary();
        $session->close();

        $restorer
            ->setBackupFilename($this->prepareAndGetBackupFilename($restorer->getExtension()))
            ->setExtraOptions(match ($this->getDatabaseSession()->getVendorName()) {
                Vendor::MARIADB => '-v --no-auto-rehash --skip-progress-reports',
                Vendor::MYSQL => '-v --no-auto-rehash',
                Vendor::POSTGRESQL => '-v -j 2 --disable-triggers --clean --if-exists',
                Vendor::SQLITE => '-bail', // No interesting options for SQLite.
                default => $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER')),
            })
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

        $this->assertSame(
            6,
            $this->getDatabaseSession()->executeQuery('select count(*) from table_in_backup_1')->fetchOne(),
        );
    }

    private function prepareAndGetBackupFilename(string $extension): string
    {
        $dir = \sprintf(
            '/tmp/%s',
            \getenv('DBAL_DRIVER')
        );

        if (!\is_dir($dir)) {
            \mkdir($dir, 777, true);
        }

        return \sprintf(
            '%s/backup_test.%s',
            $dir,
            $extension
        );
    }
}
