<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Tests\Functional\DependencyInjection;

use MakinaCorpus\DbToolsBundle\DependencyInjection\DbToolsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class KernelConfigurationTest extends TestCase
{
    private function getContainer(array $parameters = [], array $bundles = [])
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

    public function testExtensionRaiseErrorWhenUserPathDoesNotExist(): void
    {
        $config = [
            'anonymizer_paths' => [
                '/non_existing_path/'
            ],
        ];

        $extension = new DbToolsExtension();

        self::expectExceptionMessageMatches('@path "/non_existing_path/" does not exist@');
        $extension->load([$config], $this->getContainer());
    }

    public function testConfigLoadDefault(): void
    {
        $config = $this->getMinimalConfig();

        $extension = new DbToolsExtension();
        $extension->load([$config], $container = $this->getContainer());

        // No need to test them all, simply validate the config was loaded.
        self::assertTrue($container->hasDefinition('db_tools.storage'));

        $container->compile();
    }
}
