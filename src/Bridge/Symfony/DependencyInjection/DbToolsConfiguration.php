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
                    ->arrayNode('storage')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('root_dir')->defaultValue($this->getDefaultStoragePath())->end()
                            ->arrayNode('filename_strategy')
                                ->beforeNormalization()->ifString()->then(function ($v) { return ['default' => $v]; })->end()
                                ->useAttributeAsKey('connection')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('backup_expiration_age')->defaultValue('3 months ago')->end()
                    // @todo Remove in 3.x
                    ->arrayNode('excluded_tables')
                        ->setDeprecated('makinacorpus/db-tools-bundle', '2.0.0', 'Please use "db_tools.backup_excluded_tables" instead.')
                        ->beforeNormalization()->always(function ($v) { return \array_is_list($v) ? ['default' => $v] : $v; })->end()
                        ->useAttributeAsKey('connection')
                        ->arrayPrototype()
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                    ->arrayNode('backup_excluded_tables')
                        ->beforeNormalization()->always(function ($v) { return \array_is_list($v) ? ['default' => $v] : $v; })->end()
                        ->useAttributeAsKey('connection')
                        ->arrayPrototype()
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                    // @todo Remove in 3.x
                    ->arrayNode('backupper_binaries')
                        ->setDeprecated('makinacorpus/db-tools-bundle', '2.0.0', 'Please use "db_tools.backup_binaries" instead.')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('backup_binaries')
                        ->defaultValue([
                            'mariadb' => 'mariadb-dump',
                            'mysql' => 'mysqldump',
                            'postgresql' => 'pg_dump',
                            'sqlite' => 'sqlite3',
                        ])
                        ->scalarPrototype()->end()
                    ->end()
                    // @todo Remove in 3.x
                    ->arrayNode('restorer_binaries')
                        ->setDeprecated('makinacorpus/db-tools-bundle', '2.0.0', 'Please use "db_tools.restore_binaries" instead.')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('restore_binaries')
                        ->defaultValue([
                            'mariadb' => 'mariadb',
                            'mysql' => 'mysql',
                            'postgresql' => 'pg_restore',
                            'sqlite' => 'sqlite3',
                        ])
                        ->scalarPrototype()->end()
                    ->end()
                    // @todo Remove in 3.x
                    ->arrayNode('backupper_options')
                        ->setDeprecated('makinacorpus/db-tools-bundle', '2.0.0', 'Please use "db_tools.backup_options" instead.')
                        ->useAttributeAsKey('connection')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('backup_options')
                        ->useAttributeAsKey('connection')
                        ->scalarPrototype()->end()
                    ->end()
                    // @todo Remove in 3.x
                    ->arrayNode('restorer_options')
                        ->setDeprecated('makinacorpus/db-tools-bundle', '2.0.0', 'Please use "db_tools.restore_options" instead.')
                        ->useAttributeAsKey('connection')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('restore_options')
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
