<?php

namespace App\Pawellen\ListingBundle\Listing\Filter\Type;

use Symfony\Component\Form\FormBuilderInterface;


interface ListingFilterTypeInterface
{
    /**
     * @return string
     */
    public function getType(): string;


    /**
     * @return string
     */
    public function getName(): string;


    /**
     * @return FormBuilderInterface
     */
    public function getFormBuilder(): FormBuilderInterface;


    /**
     * @return array
     */
    public function getOptions(): array;
}