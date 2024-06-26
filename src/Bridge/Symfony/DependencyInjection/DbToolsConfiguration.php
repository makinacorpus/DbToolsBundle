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

    /**
     * Append values in configuration we cannot set a default.
     *
     * For example, 'anonymizer_paths', if set by the user, will loose the
     * default anonymizer paths, and we need them to be set in all cases.
     *
     * So we act after configuration has been processed and restore missing
     * values from here. This also allows the standalone configuration doing
     * it outside of Symfony extension context.
     */
    public static function appendPostConfig(array $config): array
    {
        $config['anonymizer_paths'][] = \realpath(\dirname(__DIR__, 3)) . '/Anonymization/Anonymizer';

        return $config;
    }

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('db_tools');

        $intervalToInt = function (mixed $v): null|int {
            if (null === $v) {
                return $v;
            }
            if (\is_string($v)) {
                if (\ctype_digit($v)) {
                    return (int) $v;
                }
                try {
                    if (false !== ($i = @\DateInterval::createFromDateString($v))) {
                        return $i->days * 86400 + $i->h * 3600 + $i->i * 60 + $i->s;
                    }
                } catch (\DateMalformedIntervalStringException) {
                    // Pass, invalid format.
                }
                throw throw new \InvalidArgumentException(\sprintf("Given value '%s' is not an int and cannot be parsed as a date interval", $v));
            }
            if (\is_int($v) || \is_float($v)) {
                return (int) $v;
            }
            throw throw new \InvalidArgumentException(\sprintf("Expected an int or valid date interval string value, got '%s'", \get_debug_type($v)));
        };

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
                    ->scalarNode('backup_timeout')
                        ->beforeNormalization()->always($intervalToInt)->end()
                        ->defaultValue(600)
                    ->end()
                    ->scalarNode('restore_timeout')
                        ->beforeNormalization()->always($intervalToInt)->end()
                        ->defaultValue(1800)
                    ->end()
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
