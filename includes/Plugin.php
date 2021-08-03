<?php
/**
 * Plugin Setup
 *
 * @package Plugin
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

use Towa\GebruederWeissWooCommerce\OrderStateRepository;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthAuthenticator;
use Towa\GebruederWeissWooCommerce\Options\FulfillmentOptionsTab;
use Towa\GebruederWeissWooCommerce\Options\Option;
use Towa\GebruederWeissWooCommerce\Options\OptionPage;
use Towa\GebruederWeissWooCommerce\Options\Tab;
use Towa\GebruederWeissWooCommerce\Support\Singleton;
use Towa\GebruederWeissSDK\Api\WriteApi;
use Towa\GebruederWeissSDK\ApiException;

/**
 * Main Plugin class
 */
final class Plugin extends Singleton
{
    /**
     * Option Page Slug
     */
    const OPTIONPAGESLUG = 'gbw-woocommerce';

    /**
     * The single instance of the class.
     *
     * @var Plugin
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
     * Client for writing to the Gebrueder Weiss API.
     *
     * @var WriteApi
     */
    private $writeApiClient = null;

    /** Order Controller that provides the callback handling.
     *
     * @var OrderController;
     */
     private $orderController = null;

    /**
     * Factory for building logistics orders.
     *
     * @var LogisticsOrderFactory
     */
    private $logisticsOrderFactory = null;

    /**
     * Initializes the plugin.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->initActions();
        $this->initOptionPage();
        $this->orderController = new OrderController($this->settingsRepository);
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
        \add_action('admin_init', [$this, 'validateSelectedFulfillmentStates']);
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

        if (empty($clientId) || empty($clientSecret)) {
            return;
        }

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
    public function validateSelectedFulfillmentStates(): void
    {
        $fulfillmentState = $this->settingsRepository->getFulfillmentState();
        $fulfilledState = $this->settingsRepository->getFulfilledState();
        $fulfillmentErrorState = $this->settingsRepository->getFulfillmentErrorState();

        $this->checkIfFulfillmentSettingExists($fulfillmentState, "Fulfillment State");
        $this->checkIfFulfillmentSettingExists($fulfilledState, "Fulfilled State");
        $this->checkIfFulfillmentSettingExists($fulfillmentErrorState, "Fulfillment Error State");

        if ($fulfillmentState === $fulfilledState) {
            $this->showWordpressAdminErrorMessage(
                __("The Gebrüder Weiss WooCommerce Plugin settings for Fulfillment State and Fulfilled State are set to the same state.", self::$languageDomain)
            );
        }

        if ($fulfillmentState === $fulfillmentErrorState) {
            $this->showWordpressAdminErrorMessage(
                __("The Gebrüder Weiss WooCommerce Plugin settings for Fulfillment State and Fulfillment Error State are set to the same state.", self::$languageDomain)
            );
        }

        if ($fulfilledState === $fulfillmentErrorState) {
            $this->showWordpressAdminErrorMessage(
                __("The Gebrüder Weiss WooCommerce Plugin settings for Fulfilled State and Fulfillment Error State are set to the same state.", self::$languageDomain)
            );
        }
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

        $this->updateAuthToken();
        $this->createLogisticsOrderAndUpdateOrderState($order);
    }

    /**
     * Creates a logistics order using the Gebrueder Weiss API and updates the status of the WooCommerce order.
     *
     * @param object $order The WooCommerce order.
     * @return void
     */
    public function createLogisticsOrderAndUpdateOrderState(object $order)
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($order);
        $authToken = $this->settingsRepository->getAccessToken();
        $this->writeApiClient->getConfig()->setAccessToken($authToken);

        try {
            $this->writeApiClient->logisticsOrderPost($logisticsOrder);
            $order->set_status("on-hold");
            $order->save();
        } catch (ApiException $exception) {
            if ($exception->getCode() === 400) {
                // handle faulty parameters.
                return;
            }

            if ($exception->getCode() === 409) {
                // handle conflict.
                return;
            }

            // retry request.
        }
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
     * Runs all actions that are required when uninstalling the plugin
     *
     * @return void
     */
    public static function onUninstall(): void
    {
        self::removePluginOptions();
        self::removeRequestQueueTable();
    }

    /**
     * Runs all actions that are required when activating the plugin
     *
     * @return void
     */
    public static function onActivation(): void
    {
        self::createRequestQueueTable();
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
     * Sets the client for writing to the Gebrueder Weiss API.
     *
     * @param WriteApi $client The client for writing to the api.
     * @return void
     */
    public function setWriteApiClient(WriteApi $client): void
    {
        $this->writeApiClient = $client;
    }

    /**
     * Sets the factory for creating logistics orders.
     *
     * @param LogisticsOrderFactory LogisticsOrderFactory $logisticsOrderFactory The factory.
     * @return void
     */
    public function setLogisticsOrderFactory(LogisticsOrderFactory $logisticsOrderFactory): void
    {
        $this->logisticsOrderFactory = $logisticsOrderFactory;
    }

    /**
     * Checks if the configured value for the given fulfillment setting is valid.
     *
     * @param string|null $optionValue The value of the fulfillment option.
     * @param string      $displayName The name of the setting to be shown in error messages.
     * @return void
     */
    private function checkIfFulfillmentSettingExists(?string $optionValue, string $displayName): void
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

    /**
     * Removes all options that are registered with the plugin from the WordPress options.
     *
     * @return void
     */
    private static function removePluginOptions(): void
    {
        $plugin = self::getInstance();
        $plugin->setOrderStateRepository(new OrderStateRepository());
        $plugin->initOptionPage();

        foreach ($plugin->optionsPage->getTabs() as $tab) {
            foreach ($tab->options as $option) {
                delete_option($option->slug);
            }
        }
    }

    /**
     * Creates the table for the request retry queue
     *
     * @return void
     */
    private static function createRequestQueueTable(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}gbw_request_retry_queue` (
          id int NOT NULL AUTO_INCREMENT,
          order_id int NOT NULL,
          status varchar(50) NOT NULL,
          failed_attempts int UNSIGNED NOT NULL,
          last_attempt_date DATETIME NOT NULL,
          PRIMARY KEY (id),
          UNIQUE (order_id)
        ) $charset_collate;";

        $wpdb->query($sql);
    }

    /**
     * Removes the table for the request retry queue
     *
     * @return void
     */
    private static function removeRequestQueueTable(): void
    {
        global $wpdb;
        $sql = "DROP TABLE IF EXISTS `{$wpdb->base_prefix}gbw_request_retry_queue`";
        $wpdb->query($sql);
    }
}