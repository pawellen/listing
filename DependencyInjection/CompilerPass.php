<?php
namespace Pawellen\ListingBundle\DependencyInjection;

use Pawellen\ListingBundle\Factory\Extensions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;


class CompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        // Load chain service definition:
        $extensions = $container->findDefinition(Extensions::class);

        // Register extensions:
        $taggedServices = $container->findTaggedServiceIds('pawellen.listing');
        foreach ($taggedServices as $id => $tags) {
            $extensions->addMethodCall('addExtension', [new Reference($id)]);
        }
    }

}