<?php

namespace Pawellen\ListingBundle\Listing;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Pawellen\ListingBundle\Listing\Column\ColumnBuilder;
use Pawellen\ListingBundle\Listing\Filter\FilterBuilder;


interface ListingTypeInterface
{
    /**
     * @param FilterBuilder $builder
     * @param array $options
     * @return mixed
     */
    public function buildFilters(FilterBuilder $builder, array $options): void;


    /**
     * @param ColumnBuilder $builder
     * @param array $options
     */
    public function buildColumns(ColumnBuilder $builder, array $options): void;


    /**
     * @param OptionsResolver $resolver
     */
    public function setDefaultOptions(OptionsResolver $resolver): void;


    /**
     * @return string
     */
    public function getName(): string;

}