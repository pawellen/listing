<?php

namespace Pawellen\ListingBundle\Listing\Column;

use Pawellen\ListingBundle\Listing\Column\Type\ListingColumnTypeInterface;


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


    public function rewind(): void
    {
        reset($this->columns);
    }


    public function current(): mixed
    {
        return current($this->columns);
    }


    public function key(): mixed
    {
        return key($this->columns);
    }


    #[\ReturnTypeWillChange]
    public function next(): mixed
    {
        return next($this->columns);
    }


    public function valid(): bool
    {
        return key($this->columns) !== null;
    }


    public function offsetExists($offset): bool
    {
        return isset($this->columns[$offset]);
    }


    public function offsetGet($offset): mixed
    {
        return $this->columns[$offset] ?? null;
    }


    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->columns[] = $value;
        } else {
            $this->columns[$offset] = $value;
        }
    }


    public function offsetUnset($offset): void
    {
        unset($this->columns[$offset]);
    }

}