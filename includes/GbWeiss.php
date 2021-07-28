<?php
/**
 * GbWeiss Setup
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace GbWeiss\includes;

defined('ABSPATH') || exit;

use GbWeiss\includes\OrderStateRepository;
use GbWeiss\includes\OAuth\OAuthAuthenticator;
use GbWeiss\includes\OAuth\OAuthToken;

/**
 * Main GbWeiss class
 */
final class GbWeiss extends Singleton
{
    /**
     * Option Page Slug
     */
    const OPTIONPAGESLUG = 'gbw-woocommerce';

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
     * Authentication client for the API Token.
     *
     * @var OAuthAuthenticator
     */
    private $authenticationClient = null;

    /**
     * Repository to retrieve plugin settings.
     *
     * @var SettingsRepository
     */
    private $settingsRepository = null;

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
     * Initializes the option page.
     *
     * @return void
     */
    public function initOptionPage(): void
    {
        $optionsPage = new OptionPage('options', self::OPTIONPAGESLUG);
        $accountTab = (new Tab(__('Account', self::$languageDomain), 'account'))->onTabInit([$this, 'validateCredentials']);

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
     * Initializes Wordpress actions.
     *
     * @return void
     */
    public function initActions(): void
    {
        \add_action('admin_init', [$this, 'showErrorMessageIfSelectedOrderStatesDoNotExist']);
        \add_action('admin_menu', [$this, 'addPluginPageToMenu']);
        \add_action('woocommerce_order_status_changed', [$this, "wooCommerceOrderStatusChanged"], 10, 4);
    }

    /**
     * Validates user-provided credentials on the gebrueder-weiss-api oauth endpoint
     */
    public function validateCredentials(): void
    {
        $clientId = $this->settingsRepository->getClientId();
        $clientSecret = $this->settingsRepository->getClientSecret();

        try {
            $token = $this->authenticationClient->authenticate($clientId, $clientSecret);
            if ($token && $token->isValid()) {
                self::showWordpressAdminSuccessMessage(__("Your credentials were successfully validated.", self::$languageDomain));
            } else {
                self::showWordpressAdminErrorMessage(__("Your credentials were not accepted by the Gebrüder Weiss API.", self::$languageDomain));
            }
        } catch (\Exception $e) {
            self::showWordpressAdminErrorMessage(__("Sending an authentication request to the Gebrüder Weiss API Failed.", self::$languageDomain));
        }
    }

    /**
     *  Requests a new OAuthToken and stores the accessToken in
     *  the ws_options table
     *
     *  @throws \Exception If the token could not be saved.
     */
    public function updateAuthToken(): void
    {
        $clientId = $this->settingsRepository->getClientId();
        $clientSecret = $this->settingsRepository->getClientSecret();

        $token = $this->authenticationClient->authenticate($clientId, $clientSecret);

        $this->settingsRepository->setAccessToken($token->getAccessToken());
    }

    /**
     * Checks whether the plugin is compatible with the current
     * WordPress installation and shows error messages
     * for compatibility issues in the admin panel.
     */
    public static function checkPluginCompatabilityAndPrintErrorMessages(): bool
    {
        if (!self::pluginIsCompatibleWithCurrentPhpVersion()) {
            self::showWordpressAdminErrorMessage(
                __("Gebrüder Weiss WooCommerce is not compatible with PHP " . phpversion() . ".", self::$languageDomain)
            );
            return false;
        }

        if (!self::isWooCommerceActive()) {
            self::showWordpressAdminErrorMessage(
                __("Gebrüder Weiss WooCommerce requires WooCommerce to be installed and active.", self::$languageDomain)
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
        $this->validateFulfillmentSetting($this->settingsRepository->getFulfillmentState(), "Fulfillment State");
        $this->validateFulfillmentSetting($this->settingsRepository->getFulfilledState(), "Fulfilled State");
        $this->validateFulfillmentSetting($this->settingsRepository->getFulfillmentErrorState(), "Fulfillment Error State");
    }

    /**
     * The action that should be executed when an WooCommerce Order status changes.
     *
     * @param integer $orderId  Id for the affected order.
     * @param string  $from      Original state.
     * @param string  $to        New state.
     * @param object  $order     Order object.
     * @return void
     */
    public function wooCommerceOrderStatusChanged(int $orderId, string $from, string $to, object $order)
    {
        $fulfillmentState = $this->settingsRepository->getFulfillmentState();

        // The WooCommerce order states need to have a wc- prefix. The prefix is missing when it gets passed to this function.
        $prefixedTargetState = "wc-" . $to;

        if (is_null($fulfillmentState) || $fulfillmentState !== $prefixedTargetState) {
            return;
        }

        // Send request.
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
     * Sets the OAuthAuthentication client
     *
     * @param OAuthAuthenticator $client used for oauth authentication.
     */
    public function setAuthenticationClient(OAuthAuthenticator $client): void
    {
        $this->authenticationClient = $client;
    }

    /**
     * Sets the settings repository.
     *
     * @param SettingsRepository $settingsRepository The settings repository instance.
     * @return void
     */
    public function setSettingsRepository(SettingsRepository $settingsRepository): void
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Checks if the configured value for the given fulfillment setting is valid.
     *
     * @param string $optionValue The value of the fulfillment option.
     * @param string $displayName The name of the setting to be shown in error messages.
     * @return void
     */
    private function validateFulfillmentSetting(string $optionValue, string $displayName): void
    {
        if (!$optionValue) {
            $this->showWordpressAdminErrorMessage(
                __("The Gebrüder Weiss WooCommerce Plugin settings are missing a value for " . $displayName . ".", self::$languageDomain)
            );
            return;
        }

        if (!$this->checkIfWooCommerceOrderStateExists($optionValue)) {
            $this->showWordpressAdminErrorMessage(
                __("The selected order state for " . $displayName . " in the options for the Gebrüder Weiss WooCommerce Plugin does not exist in WooCommerce.", self::$languageDomain)
            );
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
     * Shows the passed message as a success in the admin panel
     *
     * @param string $message The message to be shown in the admin panel.
     */
    private static function showWordpressAdminSuccessMessage(string $message): void
    {
        \add_action(
            "admin_notices",
            function () use ($message) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo $message ?></p>
                </div>
                <?php
            }
        );
    }
}
