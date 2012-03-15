<?php

namespace KULeuven\ShibbolethBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('shibboleth');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->scalarNode('handler_path')->end()
                ->booleanNode('secured_handler')->end()
                ->scalarNode('session_initiator_path')->end()
            ->end()
            ->fixXmlConfig('attribute_definition')
            ->children()
                ->arrayNode('attribute_definitions')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('header')->isRequired()->end()
                            ->booleanNode('multivalue')->defaultValue(false)->end()
                            ->scalarNode('charset')->defaultValue('ISO-8859-1')->end()
                        ->end()
                ->end()
             ->end()
        ;
        

        return $treeBuilder;
    }
}
