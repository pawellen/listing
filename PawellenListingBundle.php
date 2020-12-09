<?php

namespace Pawellen\ListingBundle;

use Pawellen\ListingBundle\DependencyInjection\CompilerPass;
use Pawellen\ListingBundle\Listing\ListingTypeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;


class PawellenListingBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->registerForAutoconfiguration(ListingTypeInterface::class)->addTag('pawellen.listing');
        $container->addCompilerPass(new CompilerPass());
    }

}