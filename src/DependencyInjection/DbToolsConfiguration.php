<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class DbToolsConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('db_tools');

        $treeBuilder
            ->getRootNode()
                ->children()
                    ->scalarNode('storage_directory')->defaultValue('%kernel.project_dir%/var/db_tools')->end()
                    ->scalarNode('backup_expiration_age')->defaultValue('3 months ago')->end()
                    ->arrayNode('excluded_tables')
                        ->useAttributeAsKey('connection')
                        ->arrayPrototype()
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                    ->arrayNode('backupper_binaries')
                        ->defaultValue([
                            'postgresql' => 'pg_dump',
                            'mysql' => 'mysqldump'
                        ])
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('restorer_binaries')
                        ->defaultValue([
                            'postgresql' => 'pg_restore',
                            'mysql' => 'mysql'
                        ])
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
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
