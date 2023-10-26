<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\BackupperRestorer;

use MakinaCorpus\DbToolsBundle\Backupper\MySQL\Backupper as MysqlBackupper;
use MakinaCorpus\DbToolsBundle\Backupper\PgSQL\Backupper as PgSQLBackupper;
use MakinaCorpus\DbToolsBundle\Restorer\MySQL\Restorer as MysqlRestorer;
use MakinaCorpus\DbToolsBundle\Restorer\PgSQL\Restorer as PgSQLRestorer;
use MakinaCorpus\DbToolsBundle\Tests\FunctionalTestCase;

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
    protected string $backupFilename = '/tmp/backup_test.';

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
        $connection = $this->getConnection();

        $binary = match (\getenv('DBAL_DRIVER')) {
            'pdo_mysql' => 'mysqldump',
            'pdo_pgsql' => 'pg_dump',
            default => $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'))
        };

        $backupper = match (\getenv('DBAL_DRIVER')) {
            'pdo_mysql' => new MysqlBackupper($binary, $connection),
            'pdo_pgsql' => new PgSQLBackupper($binary, $connection),
            default => $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'))
        };

        $output = $backupper->checkBinary();
        self::assertStringContainsString($binary, $output);


        $backupper
            ->setDestination($this->backupFilename . $backupper->getExtension())
            ->setVerbose(true)
            ->startBackup()
        ;

        foreach ($backupper as $data) {
            // Nothing to do there.
        }
        $backupper->checkSuccessful();
    }

    /**
     * @depends testBackupper
     */
    public function testRestorer(): void
    {
        $connection = $this->getConnection();

        // First we do some modifications to the database
        $this->dropTableIfExist('table_in_backup_2');

        $connection
            ->createQueryBuilder()
            ->delete('table_in_backup_1')
            ->where('id = 1')
            ->executeStatement()
        ;

        $binary = match (\getenv('DBAL_DRIVER')) {
            'pdo_mysql' => 'mysql',
            'pdo_pgsql' => 'pg_restore',
            default => $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'))
        };

        $restorer = match (\getenv('DBAL_DRIVER')) {
            'pdo_mysql' => new MysqlRestorer($binary, $connection),
            'pdo_pgsql' => new PgSQLRestorer($binary, $connection),
            default => $this->markTestSkipped('Driver unsupported: ' . \getenv('DBAL_DRIVER'))
        };

        $output = $restorer->checkBinary();
        self::assertStringContainsString($binary, $output);

        $connection->close();

        $restorer
            ->setBackupFilename($this->backupFilename . $restorer->getExtension())
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
}
