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

    /** @var ListingExtensions */
    protected $extensions;

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
     * @param Extensions $extensions
     * @param ListingRenderer $renderer
     * @param array $config
     */
    public function __construct(FormFactoryInterface $formFactory, ManagerRegistry $doctrine, Extensions $extensions, ListingRenderer $renderer, array $config)
    {
        $this->formFactory = $formFactory;
        $this->doctrine = $doctrine;
        $this->extensions = $extensions;
        $this->renderer = $renderer;
        $this->config = (object)$config;
    }


    /**
     * @param string $type
     * @param Request $request
     * @param array $options
     * @return Listing
     */
    public function create(string $type, Request $request, array $options = []): Listing
    {
        // Load extension or create one:
        if ($this->extensions->hasExtension($type)) {
            $listingType = $this->extensions->getExtension($type);
        } else {
            $listingType = new $type();
        }

        return $this->createListing($listingType, array_merge($options, [
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
            'view_settings'     => [],
            'alternatives'      => [],
        ]);
        $optionsResolver->addNormalizer('page_length_menu', $pageLengthMenuOptionsNormalizer);

        // Modify default options by ListingType:
        $type->setDefaultOptions($optionsResolver);

        // Resolve:
        $options = $optionsResolver->resolve($options);

        // Build:
        $filterBuilder = $this->createFilterBuilder($type, $options);
        $columnBuilder = $this->createColumnBuilder($type, $options);

        // Alternatives (allo to use more than one columns set for exaple to define custom export):
        $alternatives = [];
        foreach ($options['alternatives'] as $alternative) {
            $alternatives[$alternative] = $this->createColumnBuilder($type, $options, $alternative);
        }

        return new Listing(
            $type->getName(),
            $columnBuilder->getColumns(),
            $filterBuilder->getFilters(),
            $this->doctrine,
            $this->renderer,
            $options,
            $alternatives
        );
    }


    /**
     * @param ListingTypeInterface|null $type
     * @param array $options
     * @param string $name
     * @return ColumnBuilder
     */
    protected function createColumnBuilder(ListingTypeInterface $type = null, array $options = [], string $name = 'columns'): ColumnBuilder
    {
        $columnBuilder = new ColumnBuilder();
        if ($type instanceof ListingTypeInterface) {

            // Resolve method (typically buildColumns):
            $method = 'build' . ucfirst($name);
            if (!method_exists($type, $method)) {
                throw new \LogicException('Unable to build columns, method ' . $method . '() doeas not exists in class "' . get_class($type) . '"');
            }

            $type->$method($columnBuilder, $options);
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
            $filterBuilder = new FilterBuilder($this->formFactory, $type->getName());
            $type->buildFilters($filterBuilder, $options);
        } else {
            $filterBuilder = new FilterBuilder($this->formFactory);
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