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

use Towa\GebruederWeissWooCommerce\Actions\SendOrderAction;
use Towa\GebruederWeissWooCommerce\Options\ShippingDetailsOptionsTab;
use Towa\GebruederWeissWooCommerce\OrderStateRepository;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthAuthenticator;
use Towa\GebruederWeissWooCommerce\Options\FulfillmentOptionsTab;
use Towa\GebruederWeissWooCommerce\Options\Option;
use Towa\GebruederWeissWooCommerce\Options\OptionPage;
use Towa\GebruederWeissWooCommerce\Options\Tab;
use Towa\GebruederWeissWooCommerce\ShippingMethods\GBWShippingMethod;
use Towa\GebruederWeissWooCommerce\Support\Transient;
use Towa\GebruederWeissWooCommerce\Support\Singleton;
use Towa\GebruederWeissSDK\Api\DefaultApi;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderConflictException;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderFailedException;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\RetryFailedRequestsQueueWorker;
use Towa\GebruederWeissWooCommerce\Options\OrderOptionsTab;
use Towa\GebruederWeissWooCommerce\Support\WordPress;

/**
 * Main Plugin class
 */
final class Plugin extends Singleton
{
    const OPTION_PAGE_SLUG = 'gbw-woocommerce';

    const RETRY_REQUESTS_CRON_JOB = "gbw_retry_failed_requests";

    const CRON_EVERY_FIVE_MINUTES = "gbw_every_five_minutes";

    const LANGUAGE_DOMAIN = 'gbw-woocommerce';

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
     * @var DefaultApi
     */
    private $gebruederWeissApiClient = null;

    /**
     * Factory for building logistics orders.
     *
     * @var LogisticsOrderFactory
     */
    private $logisticsOrderFactory = null;

    /**
     * Failed Request Repository
     *
     * @var FailedRequestRepository
     */
    private $failedRequestRepository = null;

    /**
     * WooCommerce Order Repository
     *
     * @var OrderRepository
     */
    private $orderRepository = null;

    /**
     * Initializes the plugin.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->initActions();
        $this->initOptionPage();
        $this->initRestApi();
        $this->initCronJobs();
    }

    /**
     * Initializes the option page.
     *
     * @return void
     */
    public function initOptionPage(): void
    {
        $optionsPage = new OptionPage('options', self::OPTION_PAGE_SLUG);
        $accountTab = (new Tab(__('Account', self::LANGUAGE_DOMAIN), 'account'))->onTabInit([$this, 'validateCredentials']);

        $accountTab
            ->addOption(new Option('Customer Id', 'customer_id', __('Customer Id', self::LANGUAGE_DOMAIN), 'account', 'integer'))
            ->addOption(new Option('Client Id', 'client_id', __('Client Id', self::LANGUAGE_DOMAIN), 'account', 'string'))
            ->addOption(new Option('Client Secret', 'client_secret', __('Client Secret', self::LANGUAGE_DOMAIN), 'account', 'string'));

        $optionsPage->addTab($accountTab);

        $orderStatuses = $this->orderStateRepository->getAllOrderStates();

        $fulfillmentTab = new FulfillmentOptionsTab($orderStatuses);

        $optionsPage->addTab($fulfillmentTab);

        $optionsPage->addTab((new OrderOptionsTab())->onTabInit([$this, 'addCustomFieldsToOrderOptionsDropdowns']));

        $optionsPage->addTab(new ShippingDetailsOptionsTab());

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
        \add_filter('woocommerce_shipping_methods', [$this, 'addGbwShippingMethod']);
        (new SendOrderAction($this->settingsRepository))->addActions();
    }

    /**
     * Initializes the Plugin Rest Api
     *
     * @return void
     */
    public function initRestApi(): void
    {
        new OrderController($this->settingsRepository, $this->orderRepository);
    }

    /**
     * Initializes the plugin cronjobs
     *
     * @return void
     */
    public function initCronJobs(): void
    {
        self::addCronIntervals();

        WordPress::addCronjobAction(self::RETRY_REQUESTS_CRON_JOB, [$this, "runRetryFailedRequestsWorker"]);
    }

    /**
     * Validates user-provided credentials on the gebrueder-weiss-api oauth endpoint
     */
    public function validateCredentials(): void
    {
        try {
            $token = $this->authenticationClient->authenticate();
            if ($token && $token->isValid()) {
                self::showWordpressAdminSuccessMessage(__("Your credentials were successfully validated.", self::LANGUAGE_DOMAIN));
            } else {
                self::showWordpressAdminErrorMessage(__("Your credentials were not accepted by the Gebrüder Weiss API.", self::LANGUAGE_DOMAIN));
            }
        } catch (\Exception $e) {
            self::showWordpressAdminErrorMessage(__("Sending an authentication request to the Gebrüder Weiss API Failed.", self::LANGUAGE_DOMAIN));
        }
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
                __("Gebrüder Weiss WooCommerce is not compatible with PHP " . phpversion() . ".", self::LANGUAGE_DOMAIN)
            );
            return false;
        }

        if (!self::isWooCommerceActive()) {
            self::showWordpressAdminErrorMessage(
                __("Gebrüder Weiss WooCommerce requires WooCommerce to be installed and active.", self::LANGUAGE_DOMAIN)
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
        $states = [
            'Fulfillment State' => $this->settingsRepository->getFulfillmentState(),
            'Pending State' => $this->settingsRepository->getPendingState(),
            'Fulfilled State' => $this->settingsRepository->getFulfilledState(),
            'Fulfillment Error State' => $this->settingsRepository->getFulfillmentErrorState(),
        ];

        foreach ($states as $k => $v) {
            $this->checkIfFulfillmentSettingExists($v, $k);
        }

        $duplicateStates = $this->keysOfDuplicateValues($states);
        if ($duplicateStates === null) {
            $this->showWordpressAdminErrorMessage(__("keysOfDuplicateValues: provided array must only have strings"));
            return;
        }

        // Don't show error message if no duplicates have been found.
        if ($duplicateStates === []) {
            return;
        }

        $message = __("The Gebrüder Weiss WooCommerce Plugin settings have same states for:", self::LANGUAGE_DOMAIN);
        foreach ($duplicateStates as $v) {
            $message .= "<br>-" . __($v, self::LANGUAGE_DOMAIN);
        }

        $this->showWordpressAdminErrorMessage($message);
    }

    /**
     * Checks if an array of strings has duplicate values. <code>Null</code> and empty string will be ignored
     *
     * @param (string|null)[] $array The array to check.
     *
     * @return string[]|null Returns the keys of an array of strings that have same values.
     *                       Returns null if the provided array has non string and non-null values.
     */
    private function keysOfDuplicateValues(array $array): ?array
    {
        $grouped = [];
        foreach ($array as $k => $v) {
            if ($v === null || $v === "") {
                continue;
            }

            if (gettype($v) !== "string") {
                return null;
            }

            $grouped[$v][] = $k;
        }

        $result = [];
        foreach ($grouped as $v) {
            if (count($v) < 2) {
                continue;
            }

            foreach ($v as $v2) {
                $result[] = $v2;
            }
        }

        return array_unique($result);
    }

    /**
     * The action that should be executed when an WooCommerce Order status changes.
     *
     * @param integer $orderId Id for the affected order.
     * @param string  $from Original state.
     * @param string  $to New state.
     * @param object  $order Order object.
     * @return void
     */
    public function wooCommerceOrderStatusChanged(int $orderId, string $from, string $to, object $order)
    {
        $fulfillmentState = $this->settingsRepository->getFulfillmentState();

        // The WooCommerce order states need to have a wc- prefix.
        // The prefix is missing when it gets passed to this function.
        $prefixedTargetState = "wc-" . $to;

        if (is_null($fulfillmentState) || $fulfillmentState !== $prefixedTargetState) {
            return;
        }

        // If the shop owner wants to use gbw shipping zones,
        // and the order does not have the gbw shipping method, do nothing.
        if (
            $this->settingsRepository->getUseGBWShippingZones()
            && !$order->has_shipping_method(GBWShippingMethod::SHIPPING_METHOD_ID)
        ) {
            return;
        }

        $this->authenticationClient->updateAuthTokenIfNecessary();
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
        $authToken = $this->settingsRepository->getAccessToken();
        $this->gebruederWeissApiClient->getConfig()->setAccessToken($authToken->getToken());

        try {
            (new CreateLogisticsOrderCommand($order, $this->logisticsOrderFactory, $this->gebruederWeissApiClient))
                ->execute($this->settingsRepository->getPendingState());
        } catch (CreateLogisticsOrderConflictException $e) {
            $order->set_status($this->settingsRepository->getFulfillmentErrorState());
            $order->save();

            $orderId = $order->get_id();
            WordPress::sendMailToAdmin("Gebrueder Weiss Fulfillment Failed for Order #$orderId", "Creating the Gebrueder Weiss logistics order for the WooCommerce order #$orderId failed due to a conflict with the following error:\n\n{$e->getMessage()}");
        } catch (CreateLogisticsOrderFailedException $e) {
            $this->failedRequestRepository->create($order->get_id());
        }
    }

    /**
     * Retries all requests to Gebrueder Weiss that need a retry
     *
     * @return void
     */
    public function runRetryFailedRequestsWorker(): void
    {
        $this->authenticationClient->updateAuthTokenIfNecessary();

        (new RetryFailedRequestsQueueWorker(
            $this->failedRequestRepository,
            $this->logisticsOrderFactory,
            $this->gebruederWeissApiClient,
            $this->orderRepository,
            $this->settingsRepository
        ))->start();

        $this->failedRequestRepository->deleteWhereStale();
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
            self::OPTION_PAGE_SLUG,
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
        self::removeTransients();

        WordPress::clearScheduledHook(self::RETRY_REQUESTS_CRON_JOB);
    }

    /**
     * Runs all actions that are required when activating the plugin
     *
     * @return void
     */
    public static function onActivation(): void
    {
        self::createRequestQueueTable();
        self::addCronIntervals();
        self::setOrderOptionsDefaults();

        WordPress::scheduleCronjob(self::RETRY_REQUESTS_CRON_JOB, time(), self::CRON_EVERY_FIVE_MINUTES);
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
     * WooCommerce Order Repository
     *
     * @param OrderRepository $orderRepository The order repository.
     * @return void
     */
    public function setOrderRepository(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
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
     * @param DefaultApi $client The client for writing to the api.
     * @return void
     */
    public function setGebruederWeissApiClient(DefaultApi $client): void
    {
        $this->gebruederWeissApiClient = $client;
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
     * Sets the failed request repository
     *
     * @param FailedRequestRepository $failedRequestRepository The instance to be used by the plugin.
     * @return void
     */
    public function setFailedRequestRepository(FailedRequestRepository $failedRequestRepository): void
    {
        $this->failedRequestRepository = $failedRequestRepository;
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
                __("The Gebrüder Weiss WooCommerce Plugin settings are missing a value for " . $displayName . ".", self::LANGUAGE_DOMAIN)
            );
            return;
        }

        if (!$this->checkIfWooCommerceOrderStateExists($optionValue)) {
            $this->showWordpressAdminErrorMessage(
                __("The selected order state for " . $displayName . " in the options for the Gebrüder Weiss WooCommerce Plugin does not exist in WooCommerce.", self::LANGUAGE_DOMAIN)
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
     * Registers the plugin cron intervals.
     *
     * @return void
     */
    private static function addCronIntervals(): void
    {
        WordPress::addCronInterval(self::CRON_EVERY_FIVE_MINUTES, 300, __("Every 5 minutes"));
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

    /**
     * Removes all Transients associated with the plugin.
     */
    private static function removeTransients(): void
    {
        Transient::deleteTransient(Transient::META_KEYS);
    }

    /**
     * Sets the default values for the order options.
     *
     * @return void
     */
    private static function setOrderOptionsDefaults(): void
    {
        if (!WordPress::getOption(Option::OPTIONS_PREFIX . OrderOptionsTab::ORDER_ID_FIELD_NAME)) {
            WordPress::updateOption(Option::OPTIONS_PREFIX . OrderOptionsTab::ORDER_ID_FIELD_NAME, OrderOptionsTab::ORDER_ID_FIELD_DEFAULT_VALUE);
        }

        if (!Wordpress::getOption(Option::OPTIONS_PREFIX . OrderOptionsTab::CARRIER_INFORMATION_FIELD_NAME)) {
            WordPress::updateOption(Option::OPTIONS_PREFIX . OrderOptionsTab::CARRIER_INFORMATION_FIELD_NAME, OrderOptionsTab::CARRIER_INFORMATION_FIELD_DEFAULT_VALUE);
        }

        if (!Wordpress::getOption(Option::OPTIONS_PREFIX . OrderOptionsTab::TRACKING_LINK_FIELD_NAME)) {
            WordPress::updateOption(Option::OPTIONS_PREFIX . OrderOptionsTab::TRACKING_LINK_FIELD_NAME, OrderOptionsTab::TRACKING_LINK_FIELD_DEFAULT_VALUE);
        }
    }

    /**
     * Adds the custom fields to the order options dropdowns.
     * This is done to make it load only when needed, and not on every page load.
     */
    public function addCustomFieldsToOrderOptionsDropdowns()
    {
        $orderMetaKeys = Transient::getTransient(
            Transient::META_KEYS,
            [WordPress::class, 'getAllMetaKeysForPostType'],
            'shop_order',
            Transient::META_KEY_TIME_IN_SECONDS
        );

        $isOrderOptionsTab = function ($tab) {
            return $tab instanceof OrderOptionsTab;
        };

        $tabsToAddOptions = array_filter($this->optionsPage->getTabs(), $isOrderOptionsTab);
        foreach ($tabsToAddOptions as $tab) {
            foreach ($tab->options as $optionDropdown) {
                $optionDropdown->addOptions($tab->createOptionsFromFieldKeys($orderMetaKeys));
            }
        }
    }

    /**
     * Adds the GBW shipping method to the list of shipping methods, of WooCommerce.
     *
     * @param array $methods The list of shipping methods.
     */
    public function addGbwShippingMethod(array $methods): array
    {
        $methods['gbw_shipping'] = GBWShippingMethod::class;
        return $methods;
    }
}
