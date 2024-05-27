<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class DbToolsConfiguration implements ConfigurationInterface
{
    /**
     * Default storage path cannot use variable when standalone.
     */
    protected function getDefaultStoragePath(): ?string
    {
        return '%kernel.project_dir%/var/db_tools';
    }

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('db_tools');

        // @phpstan-ignore-next-line
        $treeBuilder
            ->getRootNode()
                ->children()
                    ->scalarNode('storage_directory')
                        ->setDeprecated('makinacorpus/db-tools-bundle', '1.0.1', 'Please use "db_tools.storage.root_dir" instead.')
                    ->end()
                    ->arrayNode('storage')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('root_dir')->defaultValue($this->getDefaultStoragePath())->end()
                            ->arrayNode('filename_strategy')
                                ->useAttributeAsKey('connection')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('backup_expiration_age')->defaultValue('3 months ago')->end()
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
