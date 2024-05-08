<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class DbToolsConfiguration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('db_tools');

        $treeBuilder
            ->getRootNode()
                ->children()
                    ->scalarNode('storage_directory')
                        ->setDeprecated('makinacorpus/db-tools-bundle', '1.0.1', 'Please use "db_tools.storage.root_dir" instead.')
                    ->end()
                    ->arrayNode('storage')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('root_dir')->defaultValue('%kernel.project_dir%/var/db_tools')->end()
                            ->arrayNode('filename_strategy')
                                ->useAttributeAsKey('connection')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('backup_expiration_age')->defaultValue('3 months ago')->end()
                    ->scalarNode('backup_timeout')->defaultValue(600)->end()
                    ->scalarNode('restore_timeout')->defaultValue(1800)->end()
                    ->arrayNode('excluded_tables')
                        ->useAttributeAsKey('connection')
                        ->arrayPrototype()
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                    ->arrayNode('backupper_binaries')
                        ->defaultValue([
                            'mariadb' => 'mariadb-dump',
                            'mysql' => 'mysqldump',
                            'postgresql' => 'pg_dump',
                            'sqlite' => 'sqlite3',
                        ])
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('restorer_binaries')
                        ->defaultValue([
                            'mariadb' => 'mariadb',
                            'mysql' => 'mysql',
                            'postgresql' => 'pg_restore',
                            'sqlite' => 'sqlite3',
                        ])
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('backupper_options')
                        ->useAttributeAsKey('connection')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('restorer_options')
                        ->useAttributeAsKey('connection')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('anonymizer_paths')
                        ->defaultValue([])
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('anonymization')
                        ->children()
                            ->arrayNode('yaml')
                                ->useAttributeAsKey('connection')
                                ->beforeNormalization()->ifString()->then(function ($v) { return ['default' => $v]; })->end()
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
