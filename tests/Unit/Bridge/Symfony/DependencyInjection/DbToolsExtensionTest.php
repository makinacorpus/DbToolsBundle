<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection\DbToolsExtension;
use MakinaCorpus\DbToolsBundle\Configuration\ConfigurationRegistry;
use MakinaCorpus\DbToolsBundle\Tests\Resources\FilenameStrategy\CustomFilenameStrategy;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\DependsExternal;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

class DbToolsExtensionTest extends TestCase
{
    private array $originalEnv = [];

    private function getContainer(array $parameters = [], array $bundles = []): ContainerBuilder
    {
        $container = new ContainerBuilder(new EnvPlaceholderParameterBag($parameters + [
            'kernel.bundles' => $bundles,
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.project_dir' => __DIR__,
            'kernel.root_dir' => \dirname(__DIR__),
        ]));

        return $container;
    }

    private function testExtension(array $config, ContainerBuilder $container = null): void
    {
        $container ??= $this->getContainer();
        $extension = new DbToolsExtension();
        $extension->load([$config], $container);

        // No need to test them all, simply validate the config was loaded.
        self::assertTrue($container->hasDefinition('db_tools.storage'));

        $container->compile();
    }

    #[After()]
    protected function restoreEnv(): void
    {
        try {
            foreach ($this->originalEnv as $name => $value) {
                $_ENV[$name] = $value;
                \putenv(\sprintf("%s=%s", $name, $value));
            }
        } finally {
            $this->originalEnv = [];
        }
    }

    private function putEnv(string $name, ?string $value): void
    {
        if (!\array_key_exists($name, $this->originalEnv)) {
            $originalValue = \getenv($name);
            $this->originalEnv[$name] = (false === $originalValue) ? '' : (string) $value;
        }
        $_ENV[$name] = (string) $value;
        \putenv(\sprintf("%s=%s", $name, (string) $value));
    }

    private function putEnvAll(array $values): void
    {
        foreach ($values as $name => $value) {
            $this->putEnv($name, $value);
        }
    }

    private function setAllDbToolsEnv(): void
    {
        $this->putEnvAll([
            'DBTOOLS_BACKUP_BINARY' => '/usr/bin/fromenv-backup',
            //'DBTOOLS_BACKUP_EXCLUDED_TABLES' => 'envtable1,envtable2', @todo
            'DBTOOLS_BACKUP_EXPIRATION_AGE' => '3 weeks',
            'DBTOOLS_BACKUP_OPTIONS' => '--from-env-backup',
            'DBTOOLS_BACKUP_TIMEOUT' => '666',
            'DBTOOLS_DEFAULT_CONNECTION' => 'fromenv-connection',
            'DBTOOLS_RESTORE_BINARY' => '/usr/bin/fromenv-restore',
            'DBTOOLS_RESTORE_OPTIONS' => '--from-env-restore',
            'DBTOOLS_RESTORE_TIMEOUT' => '999',
            'DBTOOLS_STORAGE_FILENAME_STRATEGY' => 'fromenv_strategy',
            'DBTOOLS_STORAGE_DIRECTORY' => '/fromenv/storage',
        ]);
    }

    #[DependsExternal(DbToolsConfigurationTest::class, 'testConfigurationFull')]
    public function testEnvVarAreOverridenByConfiguration(array $config): void
    {
        $this->setAllDbToolsEnv();

        $extension = new DbToolsExtension();
        $container = $this->getContainer();
        $container->setDefinition('one_strategy', (new Definition())->setClass(CustomFilenameStrategy::class));
        $extension->load([$config], $container);
        $container->getDefinition('db_tools.configuration.registry')->setPublic(true);
        $container->compile(true);

        $configRegistry = $container->get('db_tools.configuration.registry');
        \assert($configRegistry instanceof ConfigurationRegistry);
        $defaultConfig = $configRegistry->getDefaultConfig();

        // @todo missing excluded tables and default connection.
        self::assertSame('/path/to/dump', $defaultConfig->getBackupBinary());
        self::assertSame('2 minutes ago', $defaultConfig->getBackupExpirationAge());
        self::assertSame('--dump', $defaultConfig->getBackupOptions());
        self::assertSame(135, $defaultConfig->getBackupTimeout());
        self::assertSame('/path/to/restore', $defaultConfig->getRestoreBinary());
        self::assertSame('--restore', $defaultConfig->getRestoreOptions());
        self::assertSame(357, $defaultConfig->getRestoreTimeout());
        self::assertSame('datetime', $defaultConfig->getStorageFilenameStrategy());
        self::assertSame(__DIR__ . '/var/db_tools', $defaultConfig->getStorageDirectory());
    }

    #[DependsExternal(DbToolsConfigurationTest::class, 'testConfigurationEmpty')]
    public function testEnvVarArePropagated(array $config): void
    {
        $this->setAllDbToolsEnv();

        $extension = new DbToolsExtension();
        $extension->load([$config], $container = $this->getContainer());
        $container->getDefinition('db_tools.configuration.registry')->setPublic(true);
        $container->compile(true);

        $configRegistry = $container->get('db_tools.configuration.registry');
        \assert($configRegistry instanceof ConfigurationRegistry);
        $defaultConfig = $configRegistry->getDefaultConfig();

        // @todo missing excluded tables and default connection.
        self::assertSame('/usr/bin/fromenv-backup', $defaultConfig->getBackupBinary());
        self::assertSame('3 weeks', $defaultConfig->getBackupExpirationAge());
        self::assertSame('--from-env-backup', $defaultConfig->getBackupOptions());
        self::assertSame(666, $defaultConfig->getBackupTimeout());
        self::assertSame('/usr/bin/fromenv-restore', $defaultConfig->getRestoreBinary());
        self::assertSame('--from-env-restore', $defaultConfig->getRestoreOptions());
        self::assertSame(999, $defaultConfig->getRestoreTimeout());
        self::assertSame('fromenv_strategy', $defaultConfig->getStorageFilenameStrategy());
        self::assertSame('/fromenv/storage', $defaultConfig->getStorageDirectory());
    }

    #[DependsExternal(DbToolsConfigurationTest::class, 'testConfigurationConnectionsPartial')]
    public function testConnectionResolveParentForNonSetValues(array $config): void
    {
        $this->setAllDbToolsEnv();

        $extension = new DbToolsExtension();
        $container = $this->getContainer();
        $container->setDefinition('one_strategy', (new Definition())->setClass(CustomFilenameStrategy::class));
        $extension->load([$config], $container);
        $container->getDefinition('db_tools.configuration.registry')->setPublic(true);
        $container->compile(true);

        $configRegistry = $container->get('db_tools.configuration.registry');
        \assert($configRegistry instanceof ConfigurationRegistry);
        $defaultConfig = $configRegistry->getDefaultConfig();
        self::assertSame('/path/to/dump', $defaultConfig->getBackupBinary());

        // Non existing will inherit everything from parent.
        // This ensures we don't have any typo in Configuration class.
        $nonExistingConnectionConfig = $configRegistry->getConnectionConfig('non_existing');
        // @todo missing excluded tables and default connection.
        self::assertSame('/path/to/dump', $nonExistingConnectionConfig->getBackupBinary());
        self::assertSame('2 minutes ago', $nonExistingConnectionConfig->getBackupExpirationAge());
        self::assertSame('--dump', $nonExistingConnectionConfig->getBackupOptions());
        self::assertSame(135, $nonExistingConnectionConfig->getBackupTimeout());
        self::assertSame('/path/to/restore', $nonExistingConnectionConfig->getRestoreBinary());
        self::assertSame('--restore', $nonExistingConnectionConfig->getRestoreOptions());
        self::assertSame(357, $nonExistingConnectionConfig->getRestoreTimeout());
        self::assertSame('datetime', $nonExistingConnectionConfig->getStorageFilenameStrategy());
        self::assertSame(__DIR__ . '/var/db_tools', $nonExistingConnectionConfig->getStorageDirectory());

        $connectionConfig = $configRegistry->getConnectionConfig('connection_one');
        // @todo missing excluded tables and default connection.
        // Own values.
        self::assertSame('1 minutes ago', $connectionConfig->getBackupExpirationAge());
        self::assertSame(23, $connectionConfig->getRestoreTimeout());
        self::assertSame('one_strategy', $connectionConfig->getStorageFilenameStrategy());
        self::assertSame('/one/storage', $connectionConfig->getStorageDirectory());
        // Inherited values.
        self::assertSame('/path/to/dump', $connectionConfig->getBackupBinary());
        self::assertSame(135, $connectionConfig->getBackupTimeout());
        self::assertSame('--restore', $connectionConfig->getRestoreOptions());
        self::assertSame('/path/to/restore', $connectionConfig->getRestoreBinary());
        self::assertSame('--dump', $connectionConfig->getBackupOptions());
    }

    public function testConfigurationIsDefined(): void
    {
        self::markTestIncomplete();
    }

    public function testExtensionRaiseErrorWhenUserPathDoesNotExist(): void
    {
        $config = [];
        $config['anonymizer_paths'] = ['/non_existing_path/'];

        $extension = new DbToolsExtension();

        self::expectExceptionMessageMatches('@path "/non_existing_path/" does not exist@');
        $extension->load([$config], $this->getContainer());
    }

    public function testExtensionWithEmptyConfig(): void
    {
        $this->testExtension([]);
    }

    #[DependsExternal(DbToolsConfigurationTest::class, 'testConfigurationEmpty')]
    public function testExtensionWithMinimalConfig(array $config): void
    {
        $this->testExtension($config);
    }

    #[DependsExternal(DbToolsConfigurationTest::class, 'testConfigurationFull')]
    public function testExtensionWithFullConfig(array $config): void
    {
        $container = $this->getContainer();
        $container->setDefinition('one_strategy', (new Definition())->setClass(CustomFilenameStrategy::class));
        $this->testExtension($config, $container);
    }

    #[DependsExternal(DbToolsConfigurationTest::class, 'testConfigurationFull')]
    public function testExtensionLoadsAnonymizationConfig(array $config): void
    {
        self::markTestSkipped();

        /*
        $extension = new DbToolsExtension();
        $extension->load([$config], $container = $this->getContainer());
        $container->getDefinition('db_tools.anonymization.anonymizator.factory')->setPublic(true);
        $container->compile(true);

        $anonymizatorFactory = $container->get('db_tools.anonymization.anonymizator.factory');
        \assert($anonymizatorFactory instanceof AnonymizatorFactory);

        $anonymizator = $anonymizatorFactory->getOrCreate('connection_one');
         */
    }

    #[DependsExternal(DbToolsConfigurationTest::class, 'testConfigurationFull')]
    public function testExtensionLoadsAnonymizationFilesConfig(array $config): void
    {
        self::markTestSkipped();
    }
}
