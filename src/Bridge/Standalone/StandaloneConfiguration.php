<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Bridge\Standalone;

use MakinaCorpus\DbToolsBundle\Bridge\Symfony\DependencyInjection\DbToolsConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class StandaloneConfiguration extends DbToolsConfiguration
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = parent::getConfigTreeBuilder();

        // @phpstan-ignore-next-line
        $treeBuilder
            ->getRootNode()
                ->children()
                    ->scalarNode('workdir')
                        ->info('Directory path all other files will be relative to, if none providen then the configuration file directory will be used instead.')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('connections')
                        ->beforeNormalization()->ifString()->then(function ($v) { return ['default' => $v]; })->end()
                        ->scalarPrototype()
                            ->info('Database connection DSN/URL.')
                        ->end()
                    ->end()
                    ->scalarNode('default_connection')
                        ->info('Default connection name. If none providen, first one is used instead.')
                        ->defaultNull()
                    ->end()
                    ->arrayNode('anonymization')
                        ->children()
                            ->arrayNode('tables')
                                ->beforeNormalization()->ifString()->then(function ($v) { return ['default' => $v]; })->end()
                                ->variablePrototype()
                                    ->info('Keys are table names, values are arrays whose keys are column names and values are anonymizer configurations.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
