<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class DbToolsConfiguration implements ConfigurationInterface
{
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
                    ->arrayNode('storage')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('root_dir')->defaultNull()->end()
                            ->arrayNode('filename_strategy')
                                ->beforeNormalization()->ifString()->then(function ($v) { return ['default' => $v]; })->end()
                                ->useAttributeAsKey('connection')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('backup_expiration_age')->end()
                    ->scalarNode('backup_timeout')
                        ->beforeNormalization()->always($intervalToInt)->end()
                    ->end()
                    ->scalarNode('restore_timeout')
                        ->beforeNormalization()->always($intervalToInt)->end()
                    ->end()
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
                        ->scalarPrototype()->end()
                    ->end()
                    // @todo Remove in 3.x
                    ->arrayNode('restorer_binaries')
                        ->setDeprecated('makinacorpus/db-tools-bundle', '2.0.0', 'Please use "db_tools.restore_binaries" instead.')
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('restore_binaries')
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
