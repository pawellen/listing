<?php

namespace Pawellen\ListingBundle\Listing\Column;

use Pawellen\ListingBundle\Listing\Column\Type\ListingColumn;


class ColumnBuilder
{
    /** @var array */
    protected $children = [];


    /**
     * @param string $name
     * @param array $options
     * @return $this
     */
    public function add(string $name, array $options = [])
    {
        $column = $this->create($name, $options);
        $this->children[$name] = $column;

        return $this;
    }


    /**
     * @param string $name
     * @param array $options
     * @return ListingColumn
     */
    public function create(string $name, array $options = []): ListingColumn
    {
        return new ListingColumn($name, $options);
    }


    /**
     * @return Columns
     */
    public function getColumns(): Columns
    {
        return new Columns($this->children);
    }

}