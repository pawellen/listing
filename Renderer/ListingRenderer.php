<?php

namespace Pawellen\ListingBundle\Renderer;

use Pawellen\ListingBundle\Listing\Column\Type\ListingColumn;
use Pawellen\ListingBundle\Listing\ListingView;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Twig\Environment;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Template;
use Twig\TemplateWrapper;


class ListingRenderer
{
    /** @var Environment */
    protected $twig;

    /** @var Router */
    protected $router;

    /** @var object */
    protected $config;

    /** @var Template */
    protected $template;

    /** @var array */
    protected $blocks = [];

    /** @var bool */
    protected $silent = false;


    /**
     * ListingRenderer constructor.
     * @param Environment $twig
     * @param RouterInterface $router
     * @param array $config
     */
    public function __construct(Environment $twig, RouterInterface $router, array $config)
    {
        $this->twig = $twig;
        $this->router = $router;
        $this->config = (object)$config;
    }


    /**
     * @param ListingView $listingView
     * @return string
     */
    public function renderListing(ListingView $listingView): string
    {
        try {
            $this->load();

            return $this->template->renderBlock('listing', [
                'listing' => $listingView,
                'filters' => $listingView->getFiltersFormView()
            ], $this->blocks);
        } catch (\Throwable $t) {
            return $this->silent ? '!' : $t->getMessage();
        }
    }


    /**
     * @param ListingColumn $column
     * @param $row
     * @return string
     */
    public function renderCell(ListingColumn $column, $row): string
    {
        try {
            $this->load();

            // Load and process value:
            $values = $column->getValues($row);

            // Create template block name and parameters:
            $blockName = 'listing_' . $column->getType();
            $parameters = array_merge([
                'column' => $column,
                'row'    => $row,
            ], $values);

            // Render block:
            return $this->template->renderBlock($blockName, $parameters, $this->blocks);
        } catch (\Throwable $t) {
            return $this->silent ? '!' : $t->getMessage();
        }
    }


    /**
     * @return string
     */
    public function renderListingAssets(): string
    {
        $this->load();

        return $this->renderListingBlock('listing_assets');
    }


    /**
     * @param string $name
     * @param array $params
     * @return string
     */
    public function renderListingBlock(string $name, array $params = []): string
    {
        try {
            $this->load();

            return $this->template->renderBlock($name, $params, $this->blocks);
        } catch (\Throwable $t) {
            return $this->silent ? '!' : $t->getMessage();
        }
    }


    /**
     * @param string|null $template
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function load(string $template = null): void
    {
        if ($this->template) {
            return;
        }

        $this->template = $this->twig->load($this->config->template)->unwrap();
        $this->blocks = array_merge(
            $this->template->getBlocks() ?: [],
            $template ? $this->twig->load($template)->unwrap()->getBlocks() : []
        );
    }


    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

}