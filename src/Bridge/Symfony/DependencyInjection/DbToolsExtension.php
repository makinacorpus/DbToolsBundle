<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\DbToolsBundle\Storage\FilenameStrategyInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class DbToolsExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);
        $config = DbToolsConfiguration::appendPostConfig($config);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

        // @todo Remove in 3.x.
        $deprecationMap = [
            'backupper_binaries' => 'backup_binaries',
            'backupper_options' => 'backup_options',
            'excluded_tables' => 'backup_excluded_tables',
            'restorer_binaries' => 'restore_binaries',
            'restorer_options' => 'restore_options',
        ];
        foreach ($deprecationMap as $legacyName => $newName) {
            if (!empty($config[$legacyName])) {
                \trigger_deprecation('makinacorpus/db-tools-bundle', '2.0.0', '"db_tools.%s" configuration option is deprecated and renamed "db_tools.%s"', $legacyName, $newName);
                $config[$newName] = $config[$legacyName];
            }
            unset($config[$legacyName]);
        }

        // Those parameters default values are environment variable defaults
        // as seen in ../Resources/config/services.yaml. We override parameter
        // values only if the user explicitely defined it; otherwise it would
        // prevent environment variables completely.
        if (isset($config['backup_expiration_age'])) {
            $container->setParameter('db_tools.backup_expiration_age', $config['backup_expiration_age']);
        }
        if (isset($config['backup_options'])) { // @todo Not in env. variables yet.
            $container->setParameter('db_tools.backup_options', $config['backup_options']);
        }
        if (isset($config['backup_timeout'])) {
            $container->setParameter('db_tools.backup_timeout', $config['backup_timeout']);
        }
        if (isset($config['restore_options'])) { // @todo Not in env. variables yet.
            $container->setParameter('db_tools.restore_options', $config['restore_options']);
        }
        if (isset($config['restore_timeout'])) {
            $container->setParameter('db_tools.restore_timeout', $config['restore_timeout']);
        }
        if (isset($config['storage']['root_dir'])) {
            $container->setParameter('db_tools.storage.root_dir', $config['storage']['root_dir']);
        }

        // Special treatment for binaries, because the backupper and restorer
        // services await for an array of values.
        foreach (['backup_binaries', 'restore_binaries'] as $prefix) {
            foreach (['mariadb', 'mysql', 'postgresql', 'sqlite'] as $vendor) {
                if (isset($config[$prefix][$vendor])) {
                    $container->setParameter('db_tools.' . $prefix . '.' . $vendor, $config[$prefix][$vendor]);
                }
            }
        }

        // Those parameters are NOT in environment variables.
        // Excluded tables is dependent on the application schema and not
        // a runtime parameter, its place is not in environment variables.
        $container->setParameter('db_tools.backup_excluded_tables', $config['backup_excluded_tables'] ?? []);

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

    #[\Override]
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return new DbToolsConfiguration();
    }
}
