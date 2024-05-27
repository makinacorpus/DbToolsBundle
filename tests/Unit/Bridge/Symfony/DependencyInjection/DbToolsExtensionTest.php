<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Unit\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection\DbToolsExtension;
use PHPUnit\Framework\Attributes\DependsExternal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DbToolsExtensionTest extends TestCase
{
    private function getContainer(array $parameters = [], array $bundles = []): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag($parameters + [
            'kernel.bundles' => $bundles,
            'kernel.cache_dir' => \sys_get_temp_dir(),
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.project_dir' => __DIR__,
            'kernel.root_dir' => \dirname(__DIR__),
        ]));

        return $container;
    }

    private function getMinimalConfig(): array
    {
        return [];
    }

    private function testExtension(array $config): void
    {
        $extension = new DbToolsExtension();
        $extension->load([$config], $container = $this->getContainer());

        // No need to test them all, simply validate the config was loaded.
        self::assertTrue($container->hasDefinition('db_tools.storage'));

        $container->compile();
    }

    public function testExtensionRaiseErrorWhenUserPathDoesNotExist(): void
    {
        $config = $this->getMinimalConfig();
        $config['anonymizer_paths'] = ['/non_existing_path/'];

        $extension = new DbToolsExtension();

        self::expectExceptionMessageMatches('@path "/non_existing_path/" does not exist@');
        $extension->load([$config], $this->getContainer());
    }

    public function testExtensionFromMinimalArrayConfig(): void
    {
        $this->testExtension($this->getMinimalConfig());
    }

    #[DependsExternal(DbToolsConfigurationTest::class, 'testConfigurationMinimal')]
    public function testExtensionFromMinimalYamlConfig(array $config): void
    {
        $this->testExtension($config);
    }
}
