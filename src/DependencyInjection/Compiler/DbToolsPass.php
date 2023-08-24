<?php

namespace MakinaCorpus\DbToolsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DbToolsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('db_tools.backupper.factory.registry')) {
            $definition = $container->findDefinition('db_tools.backupper.factory.registry');

            $taggedServices = $container->findTaggedServiceIds('db_tools.backupper.factory');
            foreach ($taggedServices as $id => $tags) {
                $definition->addMethodCall('addBackupperFactory', [new Reference($id)]);
            }
        }

        if ($container->has('db_tools.restorer.factory.registry')) {
            $definition = $container->findDefinition('db_tools.restorer.factory.registry');

            $taggedServices = $container->findTaggedServiceIds('db_tools.restorer.factory');
            foreach ($taggedServices as $id => $tags) {
                $definition->addMethodCall('addRestorerFactory', [new Reference($id)]);
            }
        }

    }
}
