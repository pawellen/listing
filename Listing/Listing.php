<?php

namespace Pawellen\ListingBundle\Listing;

use Pawellen\ListingBundle\Listing\Column\Type\ListingColumn;
use Pawellen\ListingBundle\Listing\Filter\Type\ListingFilter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Pawellen\ListingBundle\Listing\Column\Columns;
use Pawellen\ListingBundle\Listing\Filter\Filters;
use Pawellen\ListingBundle\Renderer\ListingRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessor;


class Listing
{
    /** @var string */
    protected $name;

    /** @var Columns */
    protected $columns;

    /** @var Filters */
    protected $filters;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ListingRenderer */
    protected $renderer;

    /** @var array */
    protected $options;

    /** @var array */
    protected $alternatives;

    /** @var int */
    protected $allResultsCount;

    /** @var int */
    protected $firstResultsOffset;

    /** @var PropertyAccessor */
    protected $propertyAccessor;


    /**
     * Listing constructor.
     * @param string $name
     * @param Columns $columns
     * @param Filters $filters
     * @param ManagerRegistry $doctrine
     * @param ListingRenderer $renderer
     * @param array $options
     * @param array $alternatives
     */
    public function __construct(string $name, Columns $columns, Filters $filters, ManagerRegistry $doctrine, ListingRenderer $renderer, array $options = [], array $alternatives = [])
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->filters = $filters;
        $this->doctrine = $doctrine;
        $this->renderer = $renderer;
        $this->options = $options;
        $this->alternatives = $alternatives;
        $this->propertyAccessor = new PropertyAccessor();
    }


    /**
     * @return ListingView
     */
    public function createView(): ListingView
    {
        return new ListingView(
            $this->name,
            $this->columns,
            $this->filters,
            $this->options,
            $this->getInitialData(),
            $this->allResultsCount
        );
    }


    /**
     * @param Request|null $overrideRequest
     * @param array $params
     * @return JsonResponse
     */
    public function createResponse(Request $overrideRequest = null, array $params = []): JsonResponse
    {
        $data = $this->createData($overrideRequest, true);
        $data = $this->processDataAsHtml($data);
        $result = $this->createDataTablesResult($data, $params);

        return new JsonResponse($result);
    }


    /**
     * @param Request|null $overrideRequest
     * @param string|null $alternative
     * @return array
     */
    public function createExport(Request $overrideRequest = null, ?string $alternative = null): array
    {
        $data = $this->createData($overrideRequest, false);
        $data = $this->processDataAsArray($data, $alternative);

        return $data;
    }


    /**
     * @param Request|null $overrideRequest
     * @param bool $paginate
     * @return array
     */
    public function createData(Request $overrideRequest = null, bool $paginate = true): array
    {
        if (isset($this->options['data'])) {
            if (!is_array($this->options['data'])) {
                throw new \LogicException('Parameter data must be an array');
            }
            $data = $this->options['data'];
        } else {
            $request = $overrideRequest ?: $this->options['request'];

            $parameters = array_merge($request->query->all(), $request->request->all());
            $filters = [];
            if (isset($parameters['_filter']) && is_array($parameters['_filter'])) {
                $filters = $parameters['_filter'];
                unset($parameters['_filter']);
            }
            $data = $this->loadData($parameters, $filters, $paginate);
        }

        return $data;
    }

    /**
     * @return bool|null
     */
    public function isDataRequest(): ?bool
    {
        $request = $this->options['request'] ?? null;
        if ($request instanceof Request) {
            return $request->isXmlHttpRequest();
        }

        return null;
    }


    /**
     * @return array
     */
    protected function getInitialData(): array
    {
        if ($this->options['defer_load']) {
            return [];
        }

        $parameters = array_merge($this->options['request']->query->all(), $this->options['request']->request->all());

        $filters = [];
        if (isset($parameters['_filter']) && is_array($parameters['_filter'])) {
            $filters = $parameters['_filter'];
            unset($parameters['_filter']);
        }

        if (!isset($parameters['length']) || $parameters['length'] < 1) {
            $parameters['length'] = $this->options['page_length'];
        }

        $data = $this->loadData($parameters, $filters);
        $data = $this->processInitialData($data);

        return $data;
    }


    /**
     * @param array $parameters
     * @param array $filters
     * @param bool $paginate
     * @return array
     */
    protected function loadData(array $parameters, array $filters = [], bool $paginate = true): array
    {
        // Load DataTables parameters:
        $limit                  = isset($parameters['length']) && $parameters['length'] > 0 ? (int)$parameters['length'] : 0;
        $offset                 = isset($parameters['start'])  && $parameters['start'] > 0  ? (int)$parameters['start']  : 0;
        $orderColumnDefinitions = isset($parameters['order']) && is_array($parameters['order']) ? $parameters['order'] : null;

        // Create QueryBuilder:
        $queryBuilder = $this->createQueryBuilder();

        // Filters:
        $this->applyFilters($queryBuilder, $filters);

        // Sorting:
        $this->applySorting($queryBuilder, $orderColumnDefinitions);

        // Fetch results:
        $data = [];
        $processRowCallback = isset($this->options['process_row_callback']) && is_callable($this->options['process_row_callback']);

        if ($paginate) {
            // Set limits:
            if ($limit > 0) {
                $queryBuilder->setFirstResult($offset);
                $queryBuilder->setMaxResults($limit);
            }

            // Execute query using paginator:
            $paginator = new Paginator($queryBuilder->getQuery(), true);
            $this->firstResultsOffset = $limit;
            $this->allResultsCount = count($paginator);

            // Fill data array:
            foreach ($paginator as $row) {
                $data[] = $processRowCallback ? $this->options['process_row_callback']($row) : $row;
            }
        } else {
            // Get all matching results:
            foreach ($queryBuilder->getQuery()->getResult() as $row) {
                $data[] = $processRowCallback ? $this->options['process_row_callback']($row) : $row;
            }
            $this->firstResultsOffset = 0;
            $this->allResultsCount = count($data);
        }

        // Process result event:
        if (isset($this->options['process_result_callback']) && is_callable($this->options['process_result_callback'])) {
            $data = $this->options['process_result_callback']($data);
        }

        return $data;
    }


    /**
     * @param array $data
     * @param string|null $alternative
     * @return array
     */
    protected function processDataAsHtml(array $data, ?string $alternative = null): array
    {
        // Load renderer template:
        $this->renderer->load($this->options['template'] ?? null);

        $table = [];
        foreach ($data as $row) {
            $tr = $this->getRowSpecialParams($row, true);

            /** @var ListingColumn $column */
            foreach ($this->getColumns($alternative) as $column) {
                if ($column->onListing()) {
                    $tr[] = $this->renderer->renderCell($column, $row);
                }
            }
            $table[] = $tr;
        }

        return $table;
    }


    /**
     * @param array $data
     * @param string|null $alternative
     * @return array
     */
    protected function processDataAsArray(array $data, ?string $alternative = null): array
    {
        // Load renderer template:
        $this->renderer->load($this->options['template'] ?? null);

        $table = [];
        foreach ($data as $row) {
            $tr = [];
            /** @var ListingColumn $column */
            foreach ($this->getColumns($alternative) as $column) {
                if ($column->onExport()) {
                    $key = $this->renderer->renderHeaderColumn($column, $row);
                    $tr[$key] = trim(strip_tags(
                        $this->renderer->renderCell($column, $row)
                    ));
                }
            }

            $table[] = $tr;
        }

        return $table;
    }


    /**
     * @param array $data
     * @param string|null $alternative
     * @return array
     */
    protected function processInitialData(array $data, ?string $alternative = null): array
    {
        // Load renderer template:
        $this->renderer->load($this->options['template'] ?? null);

        $table = [];
        foreach ($data as $row) {
            $tr = [
                'values' => [],
                'params' => $this->getRowSpecialParams($row)
            ];
            /** @var ListingColumn $column */
            foreach ($this->getColumns($alternative) as $column) {
                if ($column->onListing()) {
                    $tr['values'][] = $this->renderer->renderCell($column, $row);
                }
            }
            $table[] = $tr;
        }

        return $table;
    }


    /**
     * @param $row
     * @param bool $isAjax
     * @return array
     */
    protected function getRowSpecialParams($row, bool $isAjax = true): array
    {
        $params = [];
        $attr = $this->options['row_attr'];

        // Id parameter:
        if (isset($attr['id']) && $attr['id'] !== null) {
            $paramName = $isAjax ? 'DT_RowId' : 'id';
            try {
                $params[$paramName] = $this->name . '_row_' . $this->propertyAccessor->getValue($row, $attr['id']);
            } catch (\Exception $e) {
                unset($params[$paramName], $attr['id']);
            };
        }

        // Class parameter:
        if (isset($attr['class']) && $attr['class'] !== null) {
            $paramName = $isAjax ? 'DT_RowClass' : 'class';
            $params[$paramName] = (string)$attr['class'];
            unset($attr['class']);
        }

        // Add custom attributes to row:
        if (is_array($attr)) {
            foreach ($attr as $name => $value) {
                if (is_callable($value)) {
                    $params['_rowAttr'][$name] = $value($row);
                } else {
                    $params['_rowAttr'][$name] = $value;
                }
            }
        }

        if ($isAjax) {
            $params['_isAjax'] = true;
        }

        return $params;
    }


    /**
     * @param $data
     * @param array $params
     * @return array
     */
    protected function createDataTablesResult($data, array $params = []): array
    {
        return array_merge([
            'sEcho' => 0,
            'iTotalRecords' => $this->allResultsCount,
            'iTotalDisplayRecords' => $this->allResultsCount,
            'data' => $data,
        ], $params);
    }


    /**
     * @param QueryBuilder $queryBuilder
     * @param array $filters
     */
    protected function applyFilters(QueryBuilder $queryBuilder, array $filters): void
    {
        $arg = 0;
        foreach ($filters as $name => $value) {
            if (!isset($this->filters[$name]) || (string)$value === '') {
                continue;
            }

            /** @var ListingFilter $filter */
            $filter = $this->filters[$name];
            $options = $filter->getOptions();
            $value = $this->transformFilterValue($filter, $value);

            // Pass QueryBuilder to modify query for this filter
            if (isset($options['query_builder'])) {
                if ($options['query_builder'] instanceof \Closure) {
                    $options['query_builder']($queryBuilder, $value);
                } else {
                    throw new \LogicException('Exception in filter "' . $filter->getName() . ', "query_builder" must be instance of \Closure.');
                }

            } elseif (isset($options['expression'])) {
                $expression = $options['expression'];
                $parameters_count = substr_count($expression, '?');
                for ($i = 0; $i < $parameters_count; $i++, $arg++) {
                    $expression = preg_replace('/\?/', ':arg_'.$arg, $expression, 1);
                    $queryBuilder->setParameter(':arg_'.$arg, $value);
                }
                $queryBuilder->andWhere($expression);
            } else {
                $field = $this->getRootAliasFieldName($queryBuilder, $filter->getName());
                if ($filter->getFormBuilder()->getType() == 'entity') {
                    $queryBuilder->andWhere($field . ' = :arg_id');
                } else {
                    $queryBuilder->andWhere($field . ' LIKE :arg_id');
                }
                $queryBuilder->setParameter(':arg_id', '%' . $value . '%');
            }
        }
    }


    /**
     * @param QueryBuilder $queryBuilder
     * @param null $orderColumnDefinitions
     */
    protected function applySorting(QueryBuilder $queryBuilder, $orderColumnDefinitions = null): void
    {
        if ($orderColumnDefinitions) {
            foreach ($orderColumnDefinitions as $orderColumnDef) {
                $orderColumn = $this->getColumns()->getByIndex($orderColumnDef['column']);
                if ($orderColumn instanceof ListingColumn && $orderColumn->isSortable()) {
                    $options = $orderColumn->getOptions();
                    if (isset($options['order_by'])) {
                        $orderProperty = $options['order_by'];
                    } else {
                        $orderProperty = $this->getRootAliasFieldName($queryBuilder, $orderColumn->getName());
                    }
                    $orderDirection = $orderColumnDef['dir'] == 'desc' ? 'DESC' : 'ASC';
                    $queryBuilder->addOrderBy($orderProperty, $orderDirection);
                }

            }
        } elseif (isset($this->options['order_by'])) {
            $orderDirection = isset($this->options['order_direction']) ? $this->options['order_direction'] : 'ASC';
            $queryBuilder->orderBy($this->options['order_by'], $orderDirection);
        }
    }


    /**
     * @param ListingFilter $filter
     * @param $value
     * @return string
     */
    protected function transformFilterValue(ListingFilter $filter, $value)
    {
        $options = $filter->getOptions();

        // To delete (ensure is compatible with previous version:
        if (isset($options['eval']) && !isset($options['transform']))
            $options['transform'] = $options['eval'];

        if (!isset($options['transform'])) {

            return $value;
        }

        switch ($options['transform']) {
            case '%like%':
                return '%' . $value . '%';

            case 'like%':
                return $value . '%';

            default:
                throw new \LogicException('Unsupported transform option "' . $options['eval'] . '" for filter "' . $filter->getName() . '"');
        }
    }


    /**
     * @return QueryBuilder
     */
    protected function createQueryBuilder(): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = null;
        if (isset($this->options['query_builder'])) {
            // Normalize QueryBuilder:
            if ($this->options['query_builder'] instanceof \Closure) {
                // If has option class then pass EntityRepository of this class otherwise pass new instance of QueryBuilder:
                if (isset($this->options['class'])) {
                    $repository = $this->doctrine->getRepository($this->options['class'], $this->options['entity_manger']);
                    $queryBuilder = $this->options['query_builder']($repository, $this->options);
                } else {
                    $queryBuilder = $this->doctrine->getManager($this->options['entity_manger'])->createQueryBuilder();
                    $this->options['query_builder']($queryBuilder, $this->options);
                }
            } else {
                $queryBuilder = $this->options['query_builder'];
            }
        } else {
            if (isset($this->options['class'])) {
                $queryBuilder = $this->doctrine->getManager($this->options['entity_manger'])->createQueryBuilder()
                    ->select('q')
                    ->from($this->options['class'], 'q');
            }
        }

        if (!$queryBuilder instanceof QueryBuilder) {
            throw new \LogicException('Unable to create query builder, one of options [class, query_builder] is required');
        }

        return $queryBuilder;
    }


    /**
     * @param QueryBuilder $queryBuilder
     * @param string $name
     * @return string
     */
    private function getRootAliasFieldName(QueryBuilder $queryBuilder, string $name): string
    {
        $rootAliases = $queryBuilder->getRootAliases();
        if (isset($rootAliases[0])) {
            return $rootAliases[0] . '.' . $name;
        }

        throw new \LogicException('Unable to get root alias field name for field "' . $name . '", maybe you should add "field" option to this column');
    }


    /**
     * @param string|null $name
     * @return Columns
     */
    private function getColumns(?string $name = 'columns'): Columns
    {
        if ($name === null || $name === 'columns') {
            return $this->columns;
        } else {
            if (empty($this->alternatives[$name])) {
                throw new \LogicException('Undefined columns alternative "' . $name . '"');
            }

            return $this->alternatives[$name]->getColumns();
        }
    }

}