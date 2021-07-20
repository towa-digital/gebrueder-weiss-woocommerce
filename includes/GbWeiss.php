<?php
/**
 * GbWeiss Setup
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace GbWeiss\includes;

use GbWeiss\includes\OrderStateRepository;

/**
 * Main GbWeiss class
 */
final class GbWeiss extends Singleton
{
    /**
     * The single instance of the class.
     *
     * @var GbWeiss
     */
    protected static $instance = null;

    /**
     * Plugin Language Domain
     *
     * @var string
     */
    public static $languageDomain = 'gbw-woocommerce';

    /**
     * Options Page
     *
     * @var OptionPage
     */
    private $optionsPage;

    /**
     * Order State Repository
     *
     * @var OrderStateRepository
     */
    private $orderStateRepository;

    /**
     * Option Page Slug
     */
    const OPTIONPAGESLUG = 'gbw-woocommerce';

    /**
     * Initializes the plugin.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->initActions();
        $this->initOptionPage();
        $this->registerUninstallHook();
    }

    /**
     * Initializes the option page
     *
     * @return void
     */
    public function initOptionPage(): void
    {
        $optionsPage = new OptionPage('options', self::OPTIONPAGESLUG);
        $accountTab = new Tab(__('Account', self::$languageDomain), 'account');
        $accountTab
            ->addOption(new Option('Customer Id', 'customer_id', __('Customer Id', self::$languageDomain), 'account', 'integer'))
            ->addOption(new Option('Client Id', 'client_id', __('Client Id', self::$languageDomain), 'account', 'string'))
            ->addOption(new Option('Client Secret', 'client_secret', __('Client Secret', self::$languageDomain), 'account', 'string'));

        $optionsPage->addTab($accountTab);
        $orderStatuses = $this->orderStateRepository->getAllOrderStates();

        $fulfillmentTab = new FulfillmentOptionsTab($orderStatuses);

        $optionsPage->addTab($fulfillmentTab);

        $this->setOptionPage($optionsPage);
    }

    /**
     * Initializes Wordpress Actions
     *
     * @return void
     */
    public function initActions(): void
    {
        \add_action('admin_menu', [$this, 'addPluginPageToMenu']);
    }

    /**
     * Checks whether the plugin is compatible with the current
     *  WordPress installation and shows error messages
     * for compatibility issues in the admin panel.
     */
    public static function checkPluginCompatabilityAndPrintErrorMessages(): bool
    {
        if (!self::pluginIsCompatibleWithCurrentPhpVersion()) {
            self::showWordpressAdminErrorMessage(
                "Gebrüder Weiss WooCommerce is not compatible with PHP " . phpversion() . "."
            );
            return false;
        }

        if (!self::isWooCommerceActive()) {
            self::showWordpressAdminErrorMessage(
                "Gebrüder Weiss WooCommerce requires WooCommerce to be installed."
            );
            return false;
        }

        return true;
    }

    /**
     * Checks if the selected order states in the settings exist and shows error messages
     * in the admin backend if that is not the case.
     *
     * @return void
     */
    public function showErrorMessageIfSelectedOrderStatesDoNotExist(): void
    {
        $this->validateFulfillmentSetting("gbw_fulfillmentState", "Fulfillment State");
        $this->validateFulfillmentSetting("gbw_fulfilledState", "Fulfilled State");
        $this->validateFulfillmentSetting("gbw_fulfillmentErrorState", "Fulfillment Error State");
    }

    /**
     * Checks if the configured value for the given fulfillment setting is valid.
     *
     * @param string $optionName The name of the WordPress option.
     * @param string $displayName The name of the setting to be shown in error messages.
     * @return void
     */
    private function validateFulfillmentSetting(string $optionName, string $displayName): void
    {
        $optionValue = \get_option($optionName);

        if (!$optionValue) {
            $this->showWordpressAdminErrorMessage("The Gebrüder Weiss WooCommerce Plugin settings are missing a value for " . $displayName . ".");
            return;
        }

        if (!$this->checkIfWooCommerceOrderStateExists($optionValue)) {
            $this->showWordpressAdminErrorMessage("The selected order state for " . $displayName . " in the options for the Gebrüder Weiss WooCommerce Plugin does not exist in WooCommerce.");
        }
    }

    /**
     * Checks if there is an order state registered with WooCommerce for the given slug.
     *
     * @param string $slug The slug for the order state.
     * @return boolean
     */
    private function checkIfWooCommerceOrderStateExists(string $slug): bool
    {
        return !!$this->orderStateRepository->getOrderStateBySlug($slug);
    }

    /**
     * Checks if the plugin is compatible with the current PHP version.
     */
    private static function pluginIsCompatibleWithCurrentPhpVersion(): bool
    {
        if (PHP_MAJOR_VERSION === 8) {
            return true;
        }

        if (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION >= 2) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the WooCommerce plugin is active.
     */
    private static function isWooCommerceActive(): bool
    {
        // As recommended by WooCommerce, see https://docs.woocommerce.com/document/create-a-plugin/ for reference.
        return in_array(
            'woocommerce/woocommerce.php',
            apply_filters(
                'active_plugins',
                get_option('active_plugins')
            )
        );
    }


    /**
     * Shows the passed message as an error in the admin panel
     *
     * @param string $message The message to be shown in the admin panel.
     */
    private static function showWordpressAdminErrorMessage(string $message): void
    {
        \add_action(
            "admin_notices",
            function () use ($message) {
                ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo $message ?></p>
                    </div>
                <?php
            }
        );
    }

    /**
     * Set Option Page of Plugin
     *
     * @param OptionPage $optionPage Options Page to be registered.
     * @return void
     */
    public function setOptionPage(OptionPage $optionPage): void
    {
        $this->optionsPage = $optionPage;
    }

    /**
     * Getter for the registered option page
     *
     * @return OptionPage|null
     */
    public function getOptionsPage(): ?OptionPage
    {
        return $this->optionsPage;
    }

    /**
     * Sets the instance for the order state repository
     *
     * @param OrderStateRepository $orderStateRepository The order state repository.
     * @return void
     */
    public function setOrderStateRepository(OrderStateRepository $orderStateRepository)
    {
        $this->orderStateRepository = $orderStateRepository;
    }

    /**
     * Render Option Page
     *
     * @return void
     */
    public function renderOptionPage(): void
    {
        $this->optionsPage->render();
    }

    /**
     * Adds Options Page for Plugin under Settings
     *
     * @return void
     */
    public function addPluginPageToMenu(): void
    {
        \add_options_page(
            __('options', 'gbw-woocommerce'),
            __('Gebrüder Weiss Woocommerce', 'gbw-woocommerce'),
            'manage_options',
            self::OPTIONPAGESLUG,
            [$this, 'renderOptionPage']
        );
    }

    /**
     * Register uninstall hook
     *
     * @return void
     */
    public function registerUninstallHook(): void
    {
        \register_uninstall_hook(__FILE__, 'uninstall');
    }

    /**
     * Uninstall Plugin
     *
     * @return void
     */
    public static function uninstall(): void
    {
        $plugin = self::getInstance();

        foreach ($plugin->optionsPage->getTabs() as $tab) {
            foreach ($tab->options as $option) {
                delete_option($option->slug);
            }
        }
    }
}
