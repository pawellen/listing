<?php

namespace Pawellen\ListingBundle\Listing;

use Pawellen\ListingBundle\Listing\Filter\FilterBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;


abstract class AbstractListingType implements ListingTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildFilters(FilterBuilder $builder, array $options): void
    {
        // do not add any filters
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }


    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return str_replace('\\', '_', get_class($this));
    }

}