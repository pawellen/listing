<?php

namespace Pawellen\ListingBundle\Listing\Column\Type;

use Symfony\Component\PropertyAccess\PropertyAccess;


abstract class ListingColumnType implements ListingColumnTypeInterface
{
    /** @var string */
    protected $name;

    /** @var array */
    protected $options;


    /**
     * @param string $name
     * @param array $options
     */
    public function __construct(string $name, array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->options['label'] ?? $this->name;
    }


    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->options['attr'] ?? [];
    }


    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }


    /**
     * @return bool
     */
    public function isSortable(): bool
    {
        return false;
    }


    /**
     * @return bool
     */
    public function isSearchable(): bool
    {
        return false;
    }


    /**
     * @return string
     */
    abstract public function getType(): string;


    /**
     * @param $row
     * @param string $propertyPath
     * @param null $emptyValue
     * @return mixed|string
     */
    protected function getPropertyValue($row, string $propertyPath, $emptyValue = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        switch (substr_count($propertyPath, '[*]')) {
            case 0:
                try {
                    $value = $propertyAccessor->getValue($row, $propertyPath);
                } catch (\Exception $e) {
                    $value = $emptyValue ?: '';
                }
                break;

            case 1:
                $iterator = 0;
                $values = array();
                while (1) {
                    try {
                        $propertyPathIterator = str_replace('[*]', '[' . $iterator . ']', $propertyPath);
                        $values[] = $propertyAccessor->getValue($row, $propertyPathIterator);
                    } catch (\Exception $e) {
                        break;
                    }
                    ++$iterator;
                }
                $value = implode(', ', $values);
                break;

            default:
                throw new \LogicException('Only one wildcard for property is allowed');
        }

        return $value;
    }
}