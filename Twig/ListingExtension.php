<?php

namespace Pawellen\ListingBundle\Twig;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Pawellen\ListingBundle\Listing\ListingView;
use Pawellen\ListingBundle\Renderer\ListingRenderer;


class ListingExtension extends AbstractExtension
{
    /** @var ListingRenderer */
    private $renderer;


    /**
     * ListingExtension constructor.
     * @param ListingRenderer $renderer
     */
    public function __construct(ListingRenderer $renderer)
    {
        $this->renderer = $renderer;
    }


    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('renderListing',           [$this, 'renderListing'],           ['is_safe' => ['html']]),
            new TwigFunction('renderListingJs',         [$this, 'renderListingJs'],         ['is_safe' => ['html']]),
            new TwigFunction('renderListingCss',        [$this, 'renderListingCss'],        ['is_safe' => ['html']]),
            new TwigFunction('renderListingAssets',     [$this, 'renderListingAssets'],     ['is_safe' => ['html']]),
        ];
    }


    /**
     * @param ListingView $listingView
     * @param string|null $template
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderListing(ListingView $listingView, ?string $template = null): string
    {
        $this->renderer->load($template ?: $listingView->getTemplateReference());

        return $this->renderer->renderListing($listingView);
    }


    /**
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderListingAssets(): string
    {
        static $isRendered = false;
        if (!$isRendered) {
            $isRendered = true;
        } else {
            return '';
        }

        return $this->renderer->renderListingAssets();
    }


    /**
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderListingJs(): string
    {
        return $this->renderer->renderListingBlock('listing_header_scripts');
    }


    /**
     * @return string
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderListingCss(): string
    {
        return $this->renderer->renderListingBlock('listing_header_styles');
    }




}