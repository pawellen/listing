<?php

namespace Pawellen\ListingBundle\Listing\Filter;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Pawellen\ListingBundle\Listing\Filter\Type\ListingFilterType;


class Filters implements \Iterator, \ArrayAccess
{
    /**
     * @var Form
     */
    protected $form;

    /**
     * @var array
     */
    protected $filters;


    /**
     * @param FormInterface $form
     * @param array $filters
     */
    public function __construct(FormInterface $form, array $filters)
    {
        $this->form = $form;
        $this->filters = $filters;
    }


    /**
     * @return Form
     */
    public function getForm(): Form
    {
        return $this->form;
    }


    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }


    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->filters);
    }


    /**
     * @param string $index
     * @return ListingFilterType|null
     */
    public function getByIndex(string $index): ?ListingFilterType
    {
        $keys = array_keys($this->filters);
        if (isset($keys[$index]) && isset($this->filters[ $keys[$index] ])) {

            return $this->filters[ $keys[$index] ];
        }

        return null;
    }


    #[\ReturnTypeWillChange]
    public function rewind()
    {
        return reset($this->filters);
    }


    /**
     * @inheritdoc
     */
    public function current()
    {
        return current($this->filters);
    }


    /**
     * @inheritdoc
     */
    public function key()
    {
        return key($this->filters);
    }


    /**
     * @inheritdoc
     */
    public function next()
    {
        return next($this->filters);
    }


    /**
     * @inheritdoc
     */
    public function valid(): bool
    {
        return key($this->filters) !== null;
    }


    /**
     * @inheritdoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->filters[$offset]);
    }


    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->filters[$offset] ?? null;
    }


    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->filters[] = $value;
        } else {
            $this->filters[$offset] = $value;
        }
    }


    /**
     * @inheritdoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->filters[$offset]);
    }
}