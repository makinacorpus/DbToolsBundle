<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection\DbToolsConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class DbToolsConfigurationTest extends TestCase
{
    private function processYamlConfiguration(array|string $dataOrFilename): array
    {
        $processor = new Processor();
        $configuration = new DbToolsConfiguration();

        return $processor->processConfiguration(
            $configuration,
            \is_string($dataOrFilename) ? Yaml::parseFile($dataOrFilename) : ['db_tools' => $dataOrFilename],
        );
    }

    private function deprecatedDefaultValues(): array
    {
        return [
            // @todo Remove in 3.x
            'backupper_binaries' => [],
            'backupper_options' => [],
            'excluded_tables' => [],
            'restorer_binaries' => [],
            'restorer_options' => [],
        ];
    }

    public function testConfigurationMinimal(): array
    {
        $result = $this->processYamlConfiguration(
            \dirname(__DIR__, 4) . '/Resources/config/packages/db_tools_min.yaml'
        );

        self::assertEquals(
            [
                'anonymizer_paths' => [],
                'backup_binaries' => [
                    'mariadb' => 'mariadb-dump',
                    'mysql' => 'mysqldump',
                    'postgresql' => 'pg_dump',
                    'sqlite' => 'sqlite3',
                ],
                'backup_expiration_age' => '3 months ago',
                'backup_excluded_tables' => [],
                'backup_options' => [],
                'backup_timeout' => 600,
                'restore_binaries' => [
                    'mariadb' => 'mariadb',
                    'mysql' => 'mysql',
                    'postgresql' => 'pg_restore',
                    'sqlite' => 'sqlite3',
                ],
                'restore_options' => [],
                'restore_timeout' => 1800,
                'storage' => [
                    'root_dir' => '%kernel.project_dir%/var/db_tools',
                    'filename_strategy' => [],
                ],
            ] + $this->deprecatedDefaultValues(),
            $result,
        );

        return $result;
    }

    public function testConfigurationAlternative1(): void
    {
        $result = $this->processYamlConfiguration(
            \dirname(__DIR__, 4) . '/Resources/config/packages/db_tools_alt1.yaml'
        );

        self::assertEquals(
            [
                'backup_expiration_age' => '6 months ago',
                'backup_excluded_tables' => [
                    'default' => ['table1', 'table2'],
                ],
                'backup_binaries' => [
                    'mariadb' => '/usr/bin/mariadb-dump',
                    'mysql' => '/usr/bin/mysqldump',
                    'postgresql' => '/usr/bin/pg_dump',
                    'sqlite' => '/usr/bin/sqlite3',
                ],
                'restore_binaries' => [
                    'mariadb' => '/usr/bin/mariadb',
                    'mysql' => '/usr/bin/mysql',
                    'postgresql' => '/usr/bin/pg_restore',
                    'sqlite' => '/usr/bin/sqlite3',
                ],
                'backup_options' => [
                    'default' => '--opt1 val1 -x -y -z --opt2 val2',
                ],
                'backup_timeout' => 1200,
                'restore_options' => [
                    'default' => '-abc -x val1 -y val2',
                ],
                'restore_timeout' => 2400,
                'anonymizer_paths' => [
                    '%kernel.project_dir%/src/Anonymization/Anonymizer',
                ],
                'anonymization' => [
                    'yaml' => [
                        'default' => '%kernel.project_dir%/config/anonymization.yaml',
                    ],
                ],
                'storage' => [
                    'root_dir' => '%kernel.project_dir%/var/backup',
                    'filename_strategy' => [],
                ],
            ] + $this->deprecatedDefaultValues(),
            $result,
        );
    }

    public function testConfigurationAlternative2(): void
    {
        $result = $this->processYamlConfiguration(
            \dirname(__DIR__, 4) . '/Resources/config/packages/db_tools_alt2.yaml'
        );

        self::assertEquals(
            [
                'storage' => [
                    'root_dir' => '%kernel.project_dir%/var/backup',
                    'filename_strategy' => [
                        'connection_two' => 'app.db_tools.custom_filename_strategy',
                    ],
                ],
                'backup_expiration_age' => '6 months ago',
                'backup_excluded_tables' => [
                    'connection_two' => ['table1', 'table2'],
                ],
                'backup_binaries' => [
                    'mariadb' => '/usr/bin/mariadb-dump',
                    'mysql' => '/usr/bin/mysqldump',
                    'postgresql' => '/usr/bin/pg_dump',
                    'sqlite' => '/usr/bin/sqlite3',
                ],
                'restore_binaries' => [
                    'mariadb' => '/usr/bin/mariadb',
                    'mysql' => '/usr/bin/mysql',
                    'postgresql' => '/usr/bin/pg_restore',
                    'sqlite' => '/usr/bin/sqlite3',
                ],
                'backup_options' => [
                    'connection_one' => '--opt1 val1 -x -y -z --opt2 val2',
                ],
                'backup_timeout' => 1800,
                'restore_options' => [
                    'connection_one' => '-abc -x val1 -y val2',
                    'connection_two' => '-a "Value 1" -bc -d val2 --end',
                ],
                'restore_timeout' => 3200,
                'anonymizer_paths' => [
                    '%kernel.project_dir%/src/Anonymization/Anonymizer',
                ],
                'anonymization' => [
                    'yaml' => [
                        'connection_one' => '%kernel.project_dir%/config/anonymizations/connection_one.yaml',
                        'connection_two' => '%kernel.project_dir%/config/anonymizations/connection_two.yaml',
                    ],
                ],
            ] + $this->deprecatedDefaultValues(),
            $result,
        );
    }

    public function testConfigurationFilenameStrategyNull(): void
    {
        $result = $this->processYamlConfiguration([
            'storage' => [
                'filename_strategy' => null,
            ],
        ]);

        self::assertEqualsCanonicalizing(
            [],
            $result['storage']['filename_strategy'],
        );
    }

    public function testConfigurationFilenameStrategyString(): void
    {
        $result = $this->processYamlConfiguration([
            'storage' => [
                'filename_strategy' => 'some_strategy',
            ],
        ]);

        self::assertSame(
            ['default' => 'some_strategy'],
            $result['storage']['filename_strategy'],
        );
    }

    public function testConfigurationFilenameStrategyArray(): void
    {
        $result = $this->processYamlConfiguration([
            'storage' => [
                'filename_strategy' => [
                    'default' => 'some_strategy'
                ],
            ],
        ]);

        self::assertSame(
            ['default' => 'some_strategy'],
            $result['storage']['filename_strategy'],
        );
    }

    public function testConfigurationBackupTimeoutInt(): void
    {
        $result = $this->processYamlConfiguration([
            'backup_timeout' => 123,
        ]);

        self::assertSame(123, $result['backup_timeout']);
    }

    public function testConfigurationBackupTimeoutIntervalString(): void
    {
        $result = $this->processYamlConfiguration([
            'backup_timeout' => '1 minute 2 seconds',
        ]);

        self::assertSame(62, $result['backup_timeout']);
    }

    public function testConfigurationBackupTimeoutInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);

        $this->processYamlConfiguration([
            'backup_timeout' => "this is not parsable",
        ]);
    }

    public function testConfigurationRestoreTimeoutInt(): void
    {
        $result = $this->processYamlConfiguration([
            'restore_timeout' => 123,
        ]);

        self::assertSame(123, $result['restore_timeout']);
    }

    public function testConfigurationRestoreTimeoutIntervalString(): void
    {
        $result = $this->processYamlConfiguration([
            'restore_timeout' => '1 minute 2 seconds',
        ]);

        self::assertSame(62, $result['restore_timeout']);
    }

    public function testConfigurationRestoreTimeoutInvalid(): void
    {
        $result = $this->processYamlConfiguration([
            'restore_timeout' => 123,
        ]);

        self::assertSame(123, $result['restore_timeout']);
    }
}
