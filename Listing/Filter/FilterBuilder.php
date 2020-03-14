<?php

namespace App\Pawellen\ListingBundle\Listing\Filter;

use App\Pawellen\ListingBundle\Listing\Filter\Type\ListingFilter;
use App\Pawellen\ListingBundle\Listing\Filter\Type\ListingFilterType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;


class FilterBuilder
{
    /** @var FormBuilderInterface */
    protected $formBuilder;

    /** @var array */
    protected $children = [];


    /**
     * @param FormFactoryInterface $formFactory
     * @param string $name
     */
    public function __construct(FormFactoryInterface $formFactory, string $name = '')
    {
        $this->formBuilder = $formFactory->createNamedBuilder($name);
    }


    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return FilterBuilder
     */
    public function add(string $name, string $type, array $options = []): FilterBuilder
    {
        $filter = $this->create($name, $type, $options);

        $this->formBuilder->add($filter->getFormBuilder());
        $this->children[$name] = $filter;

        return $this;
    }


    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return ListingFilterType
     */
    public function create(string $name, string $type, array $options = array()): ListingFilterType
    {
        $filter_options = $options['filter'] ?? [];
        if (!is_array($filter_options)) {
            throw new UnexpectedTypeException($filter_options, 'array');
        }

        unset($options['filter']);
        $formBuilder = $this->formBuilder->create($name, $type, $options);

        return new ListingFilter($name, $formBuilder, $filter_options);
    }


    /**
     * @return Filters
     */
    public function getFilters(): Filters
    {
        return new Filters($this->getForm(), $this->children);
    }


    /**
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->formBuilder->getForm();
    }

}