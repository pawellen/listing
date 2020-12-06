<?php

namespace Pawellen\ListingBundle\Listing;

use Pawellen\ListingBundle\Factory\ListingFactory;
use Pawellen\ListingBundle\Listing\Column\Columns;
use Pawellen\ListingBundle\Listing\Column\Type\ListingColumn;
use Pawellen\ListingBundle\Listing\Filter\Filters;
use Symfony\Component\Form\FormView;


class ListingView
{
    /** @var string */
    protected $name;

    /** @var Columns */
    protected $columns;

    /** @var Filters */
    protected $filters;

    /** @var array */
    protected $options;

    /** @var array */
    protected $data;

    /** @var string|null */
    protected $templateReference;

   /** @var int */
    protected $allResultCount;

    /** @var FormView|null */
    protected $formView;


    /**
     * ListingView constructor.
     * @param string $name
     * @param Columns $columns
     * @param Filters $filters
     * @param array $options
     * @param array $data
     * @param int|null $allResultCount
     */
    public function __construct(string $name, Columns $columns, Filters $filters, array $options = [], array $data = [], ?int $allResultCount = null)
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->options = $options;
        $this->data = $data;
        $this->templateReference = $options['template'] ?? null;
        $this->allResultCount = (int)$allResultCount;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns->getColumns();
    }


    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters->getFilters();
    }


    /**
     * @return FormView
     */
    public function getFiltersFormView(): FormView
    {
        if (!$this->formView) {
            // Create form:
            $form = $this->filters->getForm();

            // Submit form:
            if ($this->options['submit_filters']) {
                $form->submit(array_merge(
                    $this->options['request']->query->all(),
                    $this->options['request']->request->all()
                ));

                // Disable save state when data is submitted:
                if ($form->isSubmitted()) {
                    $this->options['save_state'] = false;
                }
            }

            // Create view:
            $this->formView = $form->createView();
            $parts = explode('_', $this->name);
            $this->formView->vars['block_prefixes'][] = ListingFactory::createCamelcaseName(end($parts));
        }

        return $this->formView;
    }


    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->options['data_source'];
    }


    /**
     * @return bool
     */
    public function hasFilters(): bool
    {
        return $this->filters->count() > 0;
    }


    /**
     * @return string|null
     */
    public function getTemplateReference(): ?string
    {
        return $this->templateReference;
    }


    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * @param array $settings
     * @return array
     */
    public function getSettings(array $settings = []): array
    {
        $columns = [];

        /** @var ListingColumn $column */
        foreach ($this->columns as $column) {
            $columns[] = [
                'searchable'    => $column->isSearchable(),
                'orderable'     => $column->isSortable(),
            ];
        }

        return array_merge([
            'pageLength'    => $this->options['page_length'],
            'columns'       => $columns,
            'deferLoading'  => (!$this->options['defer_load'] && $this->options['page_length']) ? $this->allResultCount : null,
            'lengthMenu'    => $this->options['page_length_menu'],
            'autoWidth'     => $this->options['auto_width'],
            'order'         => $this->options['order_column'],
            'stateSave'     => $this->options['save_state'],
        ], $settings, $this->options['view_settings'] ?: []);
    }


    /**
     * @param array $settings
     * @return string
     */
    public function getSettingsJson(array $settings = []): string
    {
        return json_encode($this->getSettings($settings));
    }

}