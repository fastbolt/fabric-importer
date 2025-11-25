<?php

/**
 * Copyright © Fastbolt Schraubengroßhandels GmbH.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fastbolt\FabricImporter\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @psalm-suppress MixedMethodCall
 * @codeCoverageIgnore
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('fabric_importer');
        $treeBuilder->getRootNode()
                    ->children()
                    ->integerNode('sync_entry_limit')
                        ->defaultValue(100)
                        ->end()
                    ->scalarNode('dependency_import_max_age')
                        ->defaultValue('1 hour')
                        ->end()
                    ->scalarNode('database_url')
                        ->isRequired()
                    ->end();

        return $treeBuilder;
    }
}
