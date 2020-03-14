<?php

namespace App\Pawellen\ListingBundle\DependencyInjection;

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
            ->end()
        ;

        return $treeBuilder;
    }
}