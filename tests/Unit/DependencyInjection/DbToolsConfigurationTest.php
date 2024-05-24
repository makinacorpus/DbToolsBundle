<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\DependencyInjection;

use MakinaCorpus\DbToolsBundle\DependencyInjection\DbToolsConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class DbToolsConfigurationTest extends TestCase
{
    private function processYamlConfiguration(string $yamlFilename): array
    {
        $processor = new Processor();
        $configuration = new DbToolsConfiguration();

        return $processor->processConfiguration(
            $configuration,
            Yaml::parseFile($yamlFilename)
        );
    }

    public function testConfigurationMinimal(): array
    {
        $result = $this->processYamlConfiguration(
            __DIR__ . '/../../Resources/config/packages/db_tools_min.yaml'
        );

        self::assertSame(
            [
                'storage' => [
                    'root_dir' => '%kernel.project_dir%/var/db_tools',
                    'filename_strategy' => [],
                ],
                'backup_expiration_age' => '3 months ago',
                'backup_timeout' => 600,
                'restore_timeout' => 1800,
                'excluded_tables' => [],
                'backupper_binaries' => [
                    'mariadb' => 'mariadb-dump',
                    'mysql' => 'mysqldump',
                    'postgresql' => 'pg_dump',
                    'sqlite' => 'sqlite3',
                ],
                'restorer_binaries' => [
                    'mariadb' => 'mariadb',
                    'mysql' => 'mysql',
                    'postgresql' => 'pg_restore',
                    'sqlite' => 'sqlite3',
                ],
                'backupper_options' => [],
                'restorer_options' => [],
                'anonymizer_paths' => [],
            ],
            $result
        );

        return $result;
    }

    public function testConfigurationAlternative1(): array
    {
        $result = $this->processYamlConfiguration(
            __DIR__ . '/../../Resources/config/packages/db_tools_alt1.yaml'
        );

        self::assertSame(
            [
                'storage_directory' => '%kernel.project_dir%/var/backup',
                'backup_expiration_age' => '6 months ago',
                'backup_timeout' => 1200,
                'restore_timeout' => 2400,
                'excluded_tables' => [
                    'default' => ['table1', 'table2'],
                ],
                'backupper_binaries' => [
                    'mariadb' => '/usr/bin/mariadb-dump',
                    'mysql' => '/usr/bin/mysqldump',
                    'postgresql' => '/usr/bin/pg_dump',
                    'sqlite' => '/usr/bin/sqlite3',
                ],
                'restorer_binaries' => [
                    'mariadb' => '/usr/bin/mariadb',
                    'mysql' => '/usr/bin/mysql',
                    'postgresql' => '/usr/bin/pg_restore',
                    'sqlite' => '/usr/bin/sqlite3',
                ],
                'backupper_options' => [
                    'default' => '--opt1 val1 -x -y -z --opt2 val2',
                ],
                'restorer_options' => [
                    'default' => '-abc -x val1 -y val2',
                ],
                'anonymizer_paths' => [
                    '%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymizer',
                    '%kernel.project_dir%/src/Anonymization/Anonymizer',
                ],
                'anonymization' => [
                    'yaml' => [
                        'default' => '%kernel.project_dir%/config/anonymization.yaml',
                    ],
                ],
                'storage' => [
                    'root_dir' => '%kernel.project_dir%/var/db_tools',
                    'filename_strategy' => [],
                ],
            ],
            $result
        );

        return $result;
    }

    public function testConfigurationAlternative2(): array
    {
        $result = $this->processYamlConfiguration(
            __DIR__ . '/../../Resources/config/packages/db_tools_alt2.yaml'
        );

        self::assertSame(
            [
                'storage' => [
                    'root_dir' => '%kernel.project_dir%/var/backup',
                    'filename_strategy' => [
                        'connection_two' => 'app.db_tools.custom_filename_strategy',
                    ],
                ],
                'backup_expiration_age' => '6 months ago',
                'backup_timeout' => 1800,
                'restore_timeout' => 3200,
                'excluded_tables' => [
                    'connection_two' => ['table1', 'table2'],
                ],
                'backupper_binaries' => [
                    'mariadb' => '/usr/bin/mariadb-dump',
                    'mysql' => '/usr/bin/mysqldump',
                    'postgresql' => '/usr/bin/pg_dump',
                    'sqlite' => '/usr/bin/sqlite3',
                ],
                'restorer_binaries' => [
                    'mariadb' => '/usr/bin/mariadb',
                    'mysql' => '/usr/bin/mysql',
                    'postgresql' => '/usr/bin/pg_restore',
                    'sqlite' => '/usr/bin/sqlite3',
                ],
                'backupper_options' => [
                    'connection_one' => '--opt1 val1 -x -y -z --opt2 val2',
                ],
                'restorer_options' => [
                    'connection_one' => '-abc -x val1 -y val2',
                    'connection_two' => '-a "Value 1" -bc -d val2 --end',
                ],
                'anonymizer_paths' => [
                    '%kernel.project_dir%/vendor/makinacorpus/db-tools-bundle/src/Anonymizer',
                    '%kernel.project_dir%/src/Anonymization/Anonymizer',
                ],
                'anonymization' => [
                    'yaml' => [
                        'connection_one' => '%kernel.project_dir%/config/anonymizations/connection_one.yaml',
                        'connection_two' => '%kernel.project_dir%/config/anonymizations/connection_two.yaml',
                    ],
                ],
            ],
            $result
        );

        return $result;
    }
}
