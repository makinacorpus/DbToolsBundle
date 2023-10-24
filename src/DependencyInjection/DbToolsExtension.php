<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
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

        $container->setParameter('db_tools.storage_directory', $config['storage_directory']);

        // Backupper
        $container->setParameter('db_tools.backupper.binaries', $config['backupper_binaries']);
        $container->setParameter('db_tools.backup_expiration_age', $config['backup_expiration_age']);
        $container->setParameter('db_tools.excluded_tables', $config['excluded_tables'] ?? []);

        // Restorer
        $container->setParameter('db_tools.restorer.binaries', $config['restorer_binaries']);

        // Anonymization

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
        $anonymizerPaths[] = \realpath(\dirname(__DIR__)) . '/Anonymizer';

        $container->setParameter('db_tools.anonymization.anonymizer.paths', $anonymizerPaths);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new DbToolsConfiguration();
    }
}
