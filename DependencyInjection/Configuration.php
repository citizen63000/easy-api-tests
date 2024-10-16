<?php

namespace EasyApiTests\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('easy_api_tests');
        $treeBuilder->getRootNode()
            ->children()
                    ->booleanNode('debug')->defaultTrue()->end()
                    ->scalarNode('user_class')->defaultValue(null)->end()
                    ->scalarNode('user_identity_property')->defaultValue('username')->end()
                    ->scalarNode('datetime_format')->defaultValue(\DateTimeInterface::ATOM)->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
