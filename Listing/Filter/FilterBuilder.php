<?php

namespace Pawellen\ListingBundle\Listing\Filter;

use Pawellen\ListingBundle\Listing\Filter\Type\ListingFilter;
use Pawellen\ListingBundle\Listing\Filter\Type\ListingFilterType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;


class FilterBuilder
{
    /** @var FormBuilderInterface */
    protected $formBuilder;

    /** @var array */
    protected $children = [];

    /** @var Request|null */
    protected $request;


    /**
     * @param FormFactoryInterface $formFactory
     * @param string $name
     */
    public function __construct(FormFactoryInterface $formFactory, string $name = '', ?Request $request = null)
    {
        $this->formBuilder = $formFactory->createNamedBuilder($name, 'Symfony\Component\Form\Extension\Core\Type\FormType', null, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $this->request = $request;
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
    public function create(string $name, string $type, array $options = []): ListingFilterType
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
        $form = $this->formBuilder->getForm();
        if ($this->request) {
            $form->submit($this->request->query->all());
        }

        return $form;
    }

}