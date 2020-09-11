<?php

declare(strict_types=1);

namespace Lamoda\CleanerBundle\DependencyInjection;

use Lamoda\Cleaner\DB\DoctrineDBALCleaner;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('lamoda_cleaner');

        $root = $treeBuilder->getRootNode();
        $root
            ->children()
                ->arrayNode('db')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->info('Cleaner name, specific to your project')
                        ->addDefaultsIfNotSet()
                        ->beforeNormalization()
                            ->ifTrue(function ($v) { return empty($v['queries']); })
                            ->then(function ($v) {
                                $result = ['queries' => [$v]];

                                $topLevelKeys = ['class', 'transactional', 'dbal_connection'];
                                foreach ($topLevelKeys as $key) {
                                    if (array_key_exists($key, $v)) {
                                        $result[$key] = $v[$key];
                                        unset($result['queries'][0][$key]);
                                    }
                                }

                                return $result;
                            })
                        ->end()
                        ->children()
                            ->scalarNode('class')
                                ->info('Class name')
                                ->defaultValue(DoctrineDBALCleaner::class)
                                ->cannotBeEmpty()
                            ->end()
                            ->booleanNode('transactional')
                                ->info('Wrap query/queries in transaction')
                                ->defaultTrue()
                            ->end()
                            ->scalarNode('dbal_connection')
                                ->info('Reference to Doctrine connection for DoctrineDBALCleaner')
                                ->defaultValue('database_connection')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('queries')
                                ->info('One or many queries to execute during cleanup')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('query')
                                            ->info('SQL query')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->arrayNode('parameters')
                                            ->info('List of key => value parameters for SQL')
                                            ->useAttributeAsKey('name')
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->arrayNode('types')
                                            ->info('List of parameter types if required for special SQL escape')
                                            ->scalarPrototype()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end();

        return $treeBuilder;
    }
}
