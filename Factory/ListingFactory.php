<?php

namespace Pawellen\ListingBundle\Factory;

use Pawellen\ListingBundle\Listing\Column\ColumnBuilder;
use Pawellen\ListingBundle\Listing\Filter\FilterBuilder;
use Pawellen\ListingBundle\Listing\Listing;
use Pawellen\ListingBundle\Renderer\ListingRenderer;
use Pawellen\ListingBundle\Listing\ListingTypeInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ListingFactory
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ListingRenderer */
    protected $renderer;

    /** @var object */
    private $config;

    /** @var ?string */
    protected $defaultIdProperty = null;


    /**
     * ListingFactory constructor.
     * @param FormFactoryInterface $formFactory
     * @param ManagerRegistry $doctrine
     * @param ListingRenderer $renderer
     * @param array $config
     */
    public function __construct(FormFactoryInterface $formFactory, ManagerRegistry $doctrine, ListingRenderer $renderer, array $config)
    {
        $this->formFactory = $formFactory;
        $this->doctrine = $doctrine;
        $this->renderer = $renderer;
        $this->config = (object)$config;
    }


    /**
     * @param string $typeClass
     * @param Request $request
     * @param array $options
     * @return Listing
     */
    public function create(string $typeClass, Request $request, array $options = []): Listing
    {
        return $this->createListing(new $typeClass(), array_merge($options, [
            'request' => $request
        ]));
    }


    /**
     * @param ListingTypeInterface $type
     * @param array $options
     * @return Listing
     */
    public function createListing(ListingTypeInterface $type, array $options = []): Listing
    {
        // Data source:
        $dataSourceResolver = function(Options $options) {
            if (isset($options['route'])) {
                $data_source = $this->renderer->getRouter()->generate($options['route'], $options['route_parameters'] ?? []);
            } else {
                $data_source = $options['request']->getRequestUri();
            }

            return $data_source;
        };

        // Page length:
        $pageLengthMenuOptionsNormalizer = function(Options $options, $value) {
            $lengthMenu = [];
            foreach ($value as $length) {
                $lengthMenu[0][] = $length > 0 ? (int)$length : -1;
                $lengthMenu[1][] = $length > 0 ? (int)$length : '-';
            }

            return $lengthMenu;
        };

        $columnBuilder = $this->createColumnBuilder($type, $options);
        $filterBuilder = $this->createFilterBuilder($type, $options);

        // Load default options to resolver:
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired([
            'request'
        ]);
        $optionsResolver->setDefined([
            'class',
            'query_builder',
            'process_result_callback',
            'process_row_callback',
            'order_by',
            'order_direction',
            'order_column'
        ]);

        $optionsResolver->setDefaults([
            'template'          => $this->getDefaultTemplate($options),
            'data_source'       => $dataSourceResolver,
            //'date_format'       => 'd-m-Y H:i:s',
            'page_length'       => $this->config->default_page_length ?? 10,
            'page_length_menu'  => [10, 25, 50, 100, -1],
            'auto_width'        => $this->config->default_auto_width ?? true,
            'row_attr'          => [
                'id'    => $this->defaultIdProperty ?: null,
                'class' => null
            ],
            'order_column'      => [],
            'save_state'        => $this->config->default_save_state ?? false,
            'defer_load'        => $this->config->default_defer_load ?? false,
            'submit_filters'    => true,
        ]);
        $optionsResolver->addNormalizer('page_length_menu', $pageLengthMenuOptionsNormalizer);

        // Modify default options by ListingType:
        $type->setDefaultOptions($optionsResolver);

        return new Listing(
            $type->getName(),
            $columnBuilder->getColumns(),
            $filterBuilder->getFilters(),
            $this->doctrine,
            $this->renderer,
            $optionsResolver->resolve($options)
        );
    }


    /**
     * @param ListingTypeInterface|null $type
     * @param array $options
     * @return ColumnBuilder
     */
    protected function createColumnBuilder(ListingTypeInterface $type = null, array $options = []): ColumnBuilder
    {
        $columnBuilder = new ColumnBuilder();
        if ($type instanceof ListingTypeInterface) {
            $type->buildColumns($columnBuilder, $options);
        }

        return $columnBuilder;
    }


    /**
     * @param ListingTypeInterface $type
     * @param array $options
     * @return FilterBuilder
     */
    protected function createFilterBuilder(ListingTypeInterface $type = null, array $options = []): FilterBuilder
    {
        if ($type instanceof ListingTypeInterface) {
            $filterBuilder = new FilterBuilder($this->formFactory, $options, $type->getName());
            $type->buildFilters($filterBuilder, $options);
        } else {
            $filterBuilder = new FilterBuilder($this->formFactory, $options);
        }

        return $filterBuilder;
    }


    /**
     * @param array $options
     * @return string|null
     */
    protected function getDefaultTemplate(array $options): ?string
    {
        if (isset($options['request']) && $options['request']->get('_template')) {
            return $options['request']->get('_template')->getTemplate();
        }

        return null;
    }


    /**
     * @param string $name
     * @return string
     */
    public static function createCamelcaseName(string $name): string
    {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $name)), '_');
    }

}