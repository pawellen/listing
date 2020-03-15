<?php

namespace Pawellen\ListingBundle\Listing\Column\Type;


class ListingColumn extends ListingColumnType
{
    /**
     * @return bool
     */
    public function isSortable(): bool
    {
        return isset($this->options['order_by']) && !$this->options['order_by'] ? false : true;
    }


    /**
     * @inheritdoc
     */
    public function getValues($row): array
    {
        $property = isset($this->options['property']) ? $this->options['property'] : $this->getName();
        $value = $this->getPropertyValue($row, $property);

        // Process value using callback:
        if (isset($this->options['callback']) && is_callable($this->options['callback'])) {
            $value = $this->options['callback']($value, $row, $this);
        }

        // Build parameters:
        $parameters = [];
        if (isset($this->options['parameters'])) {
            foreach ($this->options['parameters'] as $name => $propertyPath) {
                $parameters[$name] = $this->getPropertyValue($row, $propertyPath);
            }
        }

        return [
            'value' => $value,
            'parameters' => $parameters,
            'options' => $this->options,
            'name' => $this->name,
            'row' => $row
        ];
    }


    /**
     * @return string
     */
    public function getType(): string
    {
        return 'column';
    }

}