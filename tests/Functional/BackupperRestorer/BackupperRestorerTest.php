<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\BackupperRestorer;

use Doctrine\Persistence\ManagerRegistry;
use MakinaCorpus\DbToolsBundle\Backupper\BackupperFactory;
use MakinaCorpus\DbToolsBundle\Error\NotImplementedException;
use MakinaCorpus\DbToolsBundle\Restorer\RestorerFactory;
use MakinaCorpus\DbToolsBundle\Test\FunctionalTestCase;

/**
 * This class will successivly test Backupper and Restorer.
 *
 * 1. Create a table and but data in it
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

    public function testBackupper(): void
    {
        $mockDoctrineRegistry = $this->createMock(ManagerRegistry::class);
        $mockDoctrineRegistry
            ->method('getConnection')
            ->willReturn($this->getConnection())
        ;

        $backupperFactoy = new BackupperFactory($mockDoctrineRegistry, [
            'mariadb' => 'mariadb-dump',
            'mysql' => 'mysqldump',
            'postgresql' => 'pg_dump',
            'sqlite' => 'sqlite3',
        ]);

        try {
            $backupper = $backupperFactoy->create('');
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $backupper->checkBinary();
        $backupFilename = $this->prepareAndGetBackupFilename($backupper->getExtension());

        $backupper
            ->setDestination($backupFilename)
            ->setVerbose(true)
            ->startBackup()
        ;

        foreach ($backupper as $data) {
            // Nothing to do there.
        }

        $backupper->checkSuccessful();

        self::assertFileExists($backupFilename);
    }

    /**
     * @depends testBackupper
     */
    public function testRestorer(): void
    {
        $connection = $this->getConnection();
        $mockDoctrineRegistry = $this->createMock(ManagerRegistry::class);
        $mockDoctrineRegistry
            ->method('getConnection')
            ->willReturn($connection)
        ;

        // First we do some modifications to the database
        $this->dropTableIfExist('table_in_backup_2');

        $connection
            ->createQueryBuilder()
            ->delete('table_in_backup_1')
            ->where('id = 1')
            ->executeStatement()
        ;

        $restorerFactoy = new RestorerFactory($mockDoctrineRegistry, [
            'mariadb' => 'mariadb',
            'mysql' => 'mysql',
            'postgresql' => 'pg_restore',
            'sqlite' => 'sqlite3',
        ]);

        try {
            $restorer = $restorerFactoy->create('');
        } catch (NotImplementedException $e) {
            $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'));
        }

        $restorer->checkBinary();

        $connection->close();

        $restorer
            ->setBackupFilename($this->prepareAndGetBackupFilename($restorer->getExtension()))
            ->setVerbose(true)
            ->startRestore()
        ;

        foreach ($restorer as $data) {
            // Nothing to do there.
        }
        $restorer->checkSuccessful();

        // Now we check data integrity:
        // - All data from initial insert (see self::createTestData) should be there
        // - Deleted data from our previous modifications should not be

        $schemaManager = $connection->createSchemaManager();
        self::assertTrue($schemaManager->tablesExist('table_in_backup_1'));
        self::assertTrue($schemaManager->tablesExist('table_in_backup_2'));


        $this->assertSame(
            6,
            $this->getConnection()->executeQuery('select count(*) from table_in_backup_1')->fetchOne(),
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
