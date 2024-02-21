<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\DependencyInjection;

use MakinaCorpus\DbToolsBundle\Storage\FilenameStrategyInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class DbToolsExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/../config'));
        $loader->load('services.yaml');

        if (isset($config['storage_directory'])) {
            \trigger_deprecation('makinacorpus/db-tools-bundle', '1.0.1', '"db_tools.storage_directory" configuration option is deprecated and renamed "db_tools.storage.root_dir"');
            $container->setParameter('db_tools.storage.root_dir', $config['storage_directory']);
        } else {
            $container->setParameter('db_tools.storage.root_dir', $config['storage']['root_dir']);
        }

        // Backupper
        $container->setParameter('db_tools.backupper.binaries', $config['backupper_binaries']);
        $container->setParameter('db_tools.backupper.options', $config['backupper_options']);
        $container->setParameter('db_tools.backup_expiration_age', $config['backup_expiration_age']);
        $container->setParameter('db_tools.excluded_tables', $config['excluded_tables'] ?? []);

        // Restorer
        $container->setParameter('db_tools.restorer.binaries', $config['restorer_binaries']);
        $container->setParameter('db_tools.restorer.options', $config['restorer_options']);

        // Validate user-given anonymizer paths.
        $anonymizerPaths = $config['anonymizer_paths'];
        foreach ($anonymizerPaths as $userPath) {
            $resolvedUserPath = $container->getParameterBag()->resolveValue($userPath);
            if (!\is_dir($resolvedUserPath)) {
                throw new InvalidArgumentException(\sprintf('"db_tools.anonymizer_paths": path "%s" does not exist', $userPath));
            }
        }
        // Only set the default provided one if the folder exists in order to
        // prevent "directory does not exists" errors.
        $defaultDirectory = $container->getParameterBag()->resolveValue('%kernel.project_dir%/src/Anonymizer');
        if (\is_dir($defaultDirectory)) {
            $anonymizerPaths[] = '%kernel.project_dir%/src/Anonymizer';
        }
        $anonymizerPaths[] = \realpath(\dirname(__DIR__)) . '/Anonymization/Anonymizer';

        $container->setParameter('db_tools.anonymization.anonymizer.paths', $anonymizerPaths);

        // Register filename strategies.
        $strategies = [];
        foreach (($config['storage']['filename_strategy'] ?? []) as $connectioName => $strategyId) {
            // Default is handled directly by the storage service.
            if ($strategyId !== null && $strategyId !== 'default' && $strategyId !== 'datetime') {
                if ($container->hasDefinition($strategyId)) {
                    $strategies[$connectioName] = new Reference($strategyId);
                } elseif (\class_exists($strategyId)) {
                    if (!\is_subclass_of($strategyId, FilenameStrategyInterface::class)) {
                        throw new InvalidArgumentException(\sprintf('"db_tools.connections.%s.filename_strategy": class "%s" does not implement "%s"', $connectioName, $strategyId, FilenameStrategyInterface::class));
                    }
                    $serviceId = '.db_tools.filename_strategy.' . \sha1($strategyId);
                    $container->setDefinition($serviceId, (new Definition())->setClass($strategyId));
                    $strategies[$connectioName] = new Reference($serviceId);
                } else {
                    throw new InvalidArgumentException(\sprintf('"db_tools.connections.%s.filename_strategy": class or service "%s" does not exist or is not registered in container', $connectioName, $strategyId));
                }
                break;
            }
        }
        if ($strategies) {
            $container->getDefinition('db_tools.storage')->setArgument(2, $strategies);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new DbToolsConfiguration();
    }
}
