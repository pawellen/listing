<?php


namespace App\Pawellen\ListingBundle\Renderer;

use App\Pawellen\ListingBundle\Listing\Column\Type\ListingColumn;
use App\Pawellen\ListingBundle\Listing\ListingView;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Twig\Environment;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TemplateWrapper;


class ListingRenderer
{
    /** @var Environment */
    private $twig;

    /** @var Router */
    private $router;

    /** @var object */
    private $config;

    /** @var TemplateWrapper */
    private $template;


    /**
     * ListingRenderer constructor.
     * @param Environment $twig
     * @param RouterInterface $router
     * @param ContainerBagInterface $containerBag
     */
    public function __construct(Environment $twig, RouterInterface $router, ContainerBagInterface $containerBag)
    {
        $this->twig = $twig;
        $this->router = $router;
        $this->config = (object)$containerBag->get('pawellen_listing');
    }


    /**
     * @param ListingView $listingView
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Throwable
     */
    public function renderListing(ListingView $listingView): string
    {
        $this->load();

        return $this->template->renderBlock('listing', [
            'listing' => $listingView,
            'filters' => $listingView->getFiltersFormView()
        ]);
    }


    /**
     * @param ListingColumn $column
     * @param $row
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Throwable
     */
    public function renderCell(ListingColumn $column, $row): string
    {
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
        return $this->template->renderBlock($blockName, $parameters);
    }


    /**
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Throwable
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Throwable
     */
    public function renderListingBlock(string $name, array $params = []): string
    {
        $this->load();

        return $this->template->renderBlock($name, $params);
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

        $this->template = $this->twig->load($this->config->template);
    }


    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

}