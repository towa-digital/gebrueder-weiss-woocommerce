<?php
/**
 * Tab Settings
 *
 * Used to instatiate a Tab on an Optionspage
 *
 * @package GbWeissOptions
 */

namespace GbWeiss\includes;

defined('ABSPATH') || exit;

/**
 * Tab Class
 */
class Tab implements CanRender
{
    /**
     * Tab Name
     *
     * @var string
     */
    public $name;

    /**
     * Slug of Tab
     *
     * Can't contain special Characters or whitespaces
     *
     * @var string
     */
    public $slug;

    /**
     * Is the Tab currently active
     *
     * @var Boolean
     */
    public $isActive;

    /**
     * Backend Link to the current Tab
     *
     * @var string
     */
    public $link;

    /**
     * Options displayed on the Tab
     *
     * @var array
     */
    public $options;

    /**
     * Options Page Slug
     *
     * @var string
     */
    public $page;

    /**
     * Initialize Tab on Options Page
     *
     * @param string $name Name of the tab.
     * @param string $slug Slug used for the tab.
     * @param array  $options Options which should be displayed on the tab.
     * @param string $page Slug of the options page where the tab should be displayed.
     */
    public function __construct(string $name, string $slug, array $options = [], string $page = GbWeiss::OPTIONPAGESLUG)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->isActive = (isset($_GET['page']) && $_GET['page'] === $page)
        && (isset($_GET['tab']) && $_GET['tab'] === $slug);

        $this->link = "?page=" . esc_attr($page) . "&tab=" . esc_attr($slug);
        $this->page = $page;

        $this->options = $options;
        $this->addActions();
    }

    /**
     * Adds Actions of Class
     *
     * @return void
     */
    private function addActions()
    {
        \add_action('admin_init', [$this, 'addSettingsSection'], 10);
    }

    /**
     * Get Data to be Rendered
     *
     * @return array
     */
    public function getDataToRender(): array
    {
        return ['tab' => $this];
    }

    /**
     * Add Option to Tab
     *
     * @param Option $option Option to be added.
     * @return Tab
     */
    public function addOption(Option $option): Tab
    {
        $this->options[] = $option;

        return $this;
    }

    /**
     * Adds Settings Section for the tab
     *
     * @return void
     */
    public function addSettingsSection(): void
    {
        \add_settings_section(
            $this->slug,
            $this->name,
            [$this, 'render'],
            $this->page
        );
    }

    /**
     * Renders Tab
     *
     * @return void
     */
    public function render(): void
    {
        // not yet used.
    }
}
