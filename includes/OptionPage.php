<?php
/**
 * Option Page
 *
 * Used to instatiate Options Page
 *
 * @package GbWeissOptions
 */

namespace GbWeiss\includes;

/**
 * OptionsPage Class
 */
class OptionPage implements CanRender
{
    /**
     * Undocumented variable
     *
     * @var array
     */
    private $tabs = [];

    /**
     * Name of option
     *
     * @var string
     */
    private $name;

    /**
     * Slug of option
     *
     * @var string
     */
    private $slug;

    /**
     * Instatiate OptionPage
     *
     * @param string $name Name of OptionPage.
     * @param string $slug Slug to be used for OptionPage.
     */
    public function __construct($name, $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
    }

    /**
     * Add a Tab to the OptionPage
     *
     * @param Tab $tab Tab to be Added.
     * @return OptionPage
     */
    public function addTab(Tab $tab)
    {
        $this->tabs[] = $tab;

        return $this;
    }

    /**
     * Get Tabs of OptionPAge
     *
     * @return array
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * Get Data to be rendered
     *
     * @return array
     */
    public function getDataToRender(): array
    {
        return [
            'tabs' => $this->tabs
        ];
    }

    /**
     * Render OptionPage
     *
     * @return void
     */
    public function render(): void
    {
        echo TwigEnvironment::render(
            'options.twig',
            $this->getDataToRender()
        );
    }
}
