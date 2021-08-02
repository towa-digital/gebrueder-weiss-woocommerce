<?php
/**
 * Tab Settings
 *
 * Used to instatiate a Tab on an Optionspage
 *
 * @package Options
 */

namespace Towa\GebruederWeissWooCommerce\Options;

defined('ABSPATH') || exit;

use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\Contracts\CanRender;


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
     * Callbacks for onTabInit
     *
     * @var array
     */
    private $onTabInitCallbacks = [];

    /**
     * Initialize Tab on Options Page
     *
     * @param string $name Name of the tab.
     * @param string $slug Slug used for the tab.
     * @param array  $options Options which should be displayed on the tab.
     * @param string $page Slug of the options page where the tab should be displayed.
     */
    public function __construct(string $name, string $slug, array $options = [], string $page = Plugin::OPTIONPAGESLUG)
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
        \add_action('admin_head', [$this, 'fireOnTabInitIfCurrentTab'], 10);
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
     * Fires the on tab init callbacks if the tab is to be shown
     *
     * @return void
     */
    public function fireOnTabInitIfCurrentTab(): void
    {
        if (!$this->isActive) {
            return;
        }

        foreach ($this->onTabInitCallbacks as $callback) {
            $callback();
        }
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

    /**
     * Callable function run on form init.
     *
     * @param callable $callbackFunction Callable callback function.
     * @return Tab
     */
    public function onTabInit(callable $callbackFunction): Tab
    {
        /**
         * When passing the function directly to the callback array
         * PHP is not able to execute it later on. Wrapping it into
         * an anonymous closure prevents that.
         */
        $this->onTabInitCallbacks[] = function () use ($callbackFunction) {
            $callbackFunction();
        };

        return $this;
    }
}
