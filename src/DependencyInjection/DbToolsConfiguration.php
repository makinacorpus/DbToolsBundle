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
                ->scalarNode('storage_directory')->defaultValue('%kernel.project_dir%/var/private/db_tools')->end()
                ->scalarNode('backup_expiration_age')->defaultValue('3 months ago')->end()
                ->arrayNode('excluded_tables')
                    ->useAttributeAsKey('connection')
                    ->arrayPrototype()->end()
                ->end()
                ->arrayNode('backupper_binaries')
                    ->defaultValue([
                        'pgsql' => 'pg_dump',
                        'pdo_pgsql' => 'pg_dump',
                        'pdo_mysql' => 'mysqldump'
                    ])
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('restorer_binaries')
                    ->defaultValue([
                        'pgsql' => 'pg_restore',
                        'pdo_pgsql' => 'pg_restore',
                        'pdo_mysql' => 'mysql'
                    ])
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
