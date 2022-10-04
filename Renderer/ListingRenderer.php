<?php

namespace Pawellen\ListingBundle\Renderer;

use Pawellen\ListingBundle\Listing\Column\Type\ListingColumn;
use Pawellen\ListingBundle\Listing\ListingView;
use Symfony\Contracts\Translation\TranslatorInterface;
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

    /** @var TranslatorInterface  */
    protected $translator;

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
     * @param TranslatorInterface $translator
     * @param array $config
     */
    public function __construct(Environment $twig, RouterInterface $router, TranslatorInterface $translator, array $config)
    {
        $this->twig = $twig;
        $this->router = $router;
        $this->translator = $translator;
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

            // Create parameters:
            $parameters = array_merge($this->twig->getGlobals(), [
                'listing' => $listingView,
                'filters' => $listingView->getFiltersFormView()
            ]);

            return $this->template->renderBlock('listing', $parameters, $this->blocks);
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
            $parameters = array_merge($this->twig->getGlobals(), [
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
     * @param ListingColumn $column
     * @return string
     */
    public function renderHeaderColumn(ListingColumn $column): string
    {
        try {
            return $this->translator->trans($column->getLabel());
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
     * @param bool $force
     */
    public function load(string $template = null, bool $force = false): void
    {
        if ($this->template && !$force) {
            return;
        }

        // Get global template (listing_div_layout):
        $this->template = $this->twig->load($this->config->template)->unwrap();

        // Get current template (rendered by listing):
        $template = $this->twig->load($template)->unwrap();

        // Get blocks from first main layout:
        $this->blocks = $this->template->getBlocks() ?: [];

        // Get blocks from parent templates:
        $hierarchy = [];
        while ($template) {
            $hierarchy[] = $template->getBlocks() ?: [];

            // Traverse up:
            $parent = $template->getParent([]);
            if ($parent instanceof Template) {
                $template = $parent;
            } elseif ($parent instanceof TemplateWrapper) {
                $template = $parent->unwrap();
            } else {
                $template = false;
            }
        }

        // Reverse hierarchy:
        $hierarchy = array_reverse($hierarchy);

        // Join blocks:
        foreach ($hierarchy as $blocks) {
            if ($blocks) {
                $this->blocks = array_merge($this->blocks, $blocks);
            }
        }
    }


    /**
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

}
