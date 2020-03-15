<?php

namespace Pawellen\ListingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('pawellen_listing');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('template')
                    ->defaultValue('@PawellenListing/listing_div_layout.html.twig')
                ->end()
                ->integerNode('default_page_length')
                    ->defaultValue(10)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}