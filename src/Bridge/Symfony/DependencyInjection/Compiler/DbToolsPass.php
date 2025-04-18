<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader\ArrayLoader;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader\AttributesLoader;
use MakinaCorpus\DbToolsBundle\Anonymization\Config\Loader\YamlLoader;
use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection\DbToolsConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DbToolsPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $config = $this->getProcessedConfiguration($container);

        if ($container->has('db_tools.anonymization.anonymizator.factory')) {
            $anonymazorFactoryDef = $container->getDefinition('db_tools.anonymization.anonymizator.factory');

            if ($container->has('doctrine.orm.command.entity_manager_provider')) {
                $loaderId = $this->registerAttributesLoader($container);
                $anonymazorFactoryDef->addMethodCall('addConfigurationLoader', [new Reference($loaderId)]);
            }

            if (isset($config['anonymization_files'])) {
                foreach ($config['anonymization_files'] as $connectionName => $file) {
                    $loaderId = $this->registerYamlLoader($file, $connectionName, $container);
                    $anonymazorFactoryDef->addMethodCall('addConfigurationLoader', [new Reference($loaderId)]);
                }
            }

            if (isset($config['anonymization'])) {
                foreach ($config['anonymization'] as $connectionName => $data) {
                    $loaderId = $this->registerArrayLoader($data, $connectionName, $container);
                    $anonymazorFactoryDef->addMethodCall('addConfigurationLoader', [new Reference($loaderId)]);
                }
            }
        }
    }

    private function registerArrayLoader(array $data, string $connectionName, ContainerBuilder $container): string
    {
        $definition = new Definition();
        $definition->setClass(ArrayLoader::class);
        $definition->setArguments([$data, $connectionName]);

        $loaderId = 'db_tools.anonymization.loader.array.' . $connectionName;
        $container->setDefinition($loaderId, $definition);

        return $loaderId;
    }

    private function registerYamlLoader(string $file, string $connectionName, ContainerBuilder $container): string
    {
        $definition = new Definition();
        $definition->setClass(YamlLoader::class);
        $definition->setArguments([$file, $connectionName]);

        $loaderId = 'db_tools.anonymization.loader.yaml.' . $connectionName;
        $container->setDefinition($loaderId, $definition);

        return $loaderId;
    }

    private function registerAttributesLoader(ContainerBuilder $container): string
    {
        $definition = new Definition();
        $definition->setClass(AttributesLoader::class);
        $definition->setArguments([new Reference('doctrine.orm.command.entity_manager_provider')]);

        $loaderId = 'db_tools.anonymization.loader.attributes';
        $container->setDefinition($loaderId, $definition);

        return $loaderId;
    }

    private function getProcessedConfiguration(ContainerBuilder $container)
    {
        $processor = new Processor();
        $rawConfig = $container->getExtensionConfig('db_tools');

        return $processor->processConfiguration(new DbToolsConfiguration(), $rawConfig);
    }
}
