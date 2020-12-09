<?php

namespace Pawellen\ListingBundle\Factory;

use Pawellen\ListingBundle\Listing\ListingTypeInterface;


class Extensions
{
    /**
     * @var ListingTypeInterface[]
     */
    private $extensions = [];


    /**
     * @param ListingTypeInterface $type
     * @return Extensions
     */
    public function addExtension(ListingTypeInterface $type): self
    {
        $this->extensions[get_class($type)] = $type;

        return $this;
    }

    /**
     * @param string $type
     * @return ListingTypeInterface|null
     */
    public function getExtension(string $type): ?ListingTypeInterface
    {
        return $this->extensions[$type] ?? null;
    }

    /**
     * @return ListingTypeInterface[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function hasExtension(string $type): bool
    {
        return isset($this->extensions[$type]);
    }

}