<?php

namespace App\Pawellen\ListingBundle\Listing\Column;

use App\Pawellen\ListingBundle\Listing\Column\Type\ListingColumnTypeInterface;


class Columns implements \Iterator, \ArrayAccess
{
    /** @var array */
    protected $columns;


    /**
     * @param array $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }


    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }


    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->columns);
    }


    /**
     * @param string $index
     * @return ListingColumnTypeInterface|null
     */
    public function getByIndex(string $index): ?ListingColumnTypeInterface
    {
        $keys = array_keys($this->columns);
        if (isset($keys[$index]) && isset($this->columns[ $keys[$index] ])) {

            return $this->columns[ $keys[$index] ];
        }

        return null;
    }


    /**
     * @inheritdoc
     */
    public function rewind()
    {
        return reset($this->columns);
    }


    /**
     * @inheritdoc
     */
    public function current()
    {
        return current($this->columns);
    }


    /**
     * @inheritdoc
     */
    public function key()
    {
        return key($this->columns);
    }


    /**
     * @inheritdoc
     */
    public function next()
    {
        return next($this->columns);
    }


    /**
     * @inheritdoc
     */
    public function valid()
    {
        return key($this->columns) !== null;
    }


    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->columns[$offset]);
    }


    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->columns[$offset] ?? null;
    }


    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->columns[] = $value;
        } else {
            $this->columns[$offset] = $value;
        }
    }


    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->columns[$offset]);
    }

}