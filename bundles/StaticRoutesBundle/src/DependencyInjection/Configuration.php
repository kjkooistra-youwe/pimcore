<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StaticRoutesBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pimcore_static_routes');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->addDefaultsIfNotSet();

        $rootNode
            ->children()
                ->arrayNode('definitions')
                ->normalizeKeys(false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('pattern')->end()
                            ->scalarNode('reverse')->end()
                            ->scalarNode('controller')->end()
                            ->scalarNode('variables')->end()
                            ->scalarNode('defaults')->end()
                            ->arrayNode('siteId')
                                ->integerPrototype()->end()
                            ->end()
                            ->arrayNode('methods')
                                ->scalarPrototype()->end()
                            ->end()
                            ->integerNode('priority')->end()
                            ->integerNode('creationDate')->end()
                            ->integerNode('modificationDate')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        ConfigurationHelper::addConfigLocationWithWriteTargetNodes($rootNode, ['staticroutes' => PIMCORE_CONFIGURATION_DIRECTORY . '/staticroutes']);

        return $treeBuilder;
    }
}
