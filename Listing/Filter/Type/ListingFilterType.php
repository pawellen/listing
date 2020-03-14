<?php

namespace Pawellen\ListingBundle\Listing\Filter\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;


abstract class ListingFilterType implements ListingFilterTypeInterface
{
    /** @var string */
    protected $name;

    /** @var FormBuilder */
    protected $formBuilder;

    /** @var array */
    protected $options;


    /**
     * ListingFilterType constructor.
     * @param string $name
     * @param FormBuilderInterface $formBuilder
     * @param array $options
     */
    public function __construct(string $name, FormBuilderInterface $formBuilder, array $options = [])
    {
        $this->name = $name;
        $this->formBuilder = $formBuilder;
        $this->options = $options;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return FormBuilderInterface
     */
    public function getFormBuilder(): FormBuilderInterface
    {
        return $this->formBuilder;
    }


    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }


    /**
     * @return string
     */
    abstract public function getType(): string;

}