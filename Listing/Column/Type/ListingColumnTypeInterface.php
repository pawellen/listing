<?php

namespace App\Pawellen\ListingBundle\Listing\Column\Type;


interface ListingColumnTypeInterface
{
    /**
     * @return string
     */
    public function getType(): string;


    /**
     * @return string
     */
    public function getName(): string;


    /**
     * @return array
     */
    public function getOptions(): array;


    /**
     * @return bool
     */
    public function isSortable(): bool;


    /**
     * @return bool
     */
    public function isSearchable(): bool;


    /**
     * @param $row
     * @return mixed
     */
    public function getValues($row);

}