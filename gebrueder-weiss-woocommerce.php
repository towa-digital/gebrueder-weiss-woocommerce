<?php
/**
 * Gebrüder Weiss Woocommere
 *
 * @package Plugin
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Gebrüder Weiss Woocommerce
 * Plugin URI: https://github.com/towa-digital/gebrueder-weiss-woocommerce/
 * Description: Plugin for connecting Woocommerce to Gebrüder Weiss Transport Api
 * Requires PHP: ^7.3
 * Author: Towa Digital <developer@towa.at>
 * Author URI: https://www.towa-digital.com
 * License: GPLv3
 * Text Domain: gb-weiss
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

require __DIR__ . '/vendor/autoload.php';

use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthAuthenticator;
use Towa\GebruederWeissWooCommerce\OrderStateRepository;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use League\OAuth2\Client\Provider\GenericProvider;
use Towa\GebruederWeissSDK\Api\WriteApi;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;

/**
 * Use Dotenv to set required environment variables and load .env file in root
 */
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

add_action("init", function () {
    if (!Plugin::checkPluginCompatabilityAndPrintErrorMessages()) {
        return;
    };

    $plugin = Plugin::getInstance();

    $apiEndpoint = env('GEBRUEDER_WEISS_API_URL', 'https://apitest.gw-world.com:443/');
    $tokenEndpoint = env('GEBRUEDER_WEISS_OAUTH_TOKEN_URL', 'https://apitest.gw-world.com:443/token');

    $authProvider = new GenericProvider([
        // Has to be set as non-empty string to instantiate provider.
        'clientId'                => 'clientId',
        // Has to be set as non-empty string to instantiate provider.
        'clientSecret'            => 'clientSecret',
        'redirectUri'             => null,
        'urlAuthorize'            => null,
        'urlAccessToken'          => $tokenEndpoint,
        'urlResourceOwnerDetails' => null
    ]);
    $authenticationClient = new OAuthAuthenticator($authProvider);

    $writeApi = new WriteApi();
    $writeApi->getConfig()->setHost($apiEndpoint);

    $settingsRepository = new SettingsRepository();
    $logisticsOrderFactory = new LogisticsOrderFactory($settingsRepository);

    $plugin->setAuthenticationClient($authenticationClient);
    $plugin->setOrderStateRepository(new OrderStateRepository());
    $plugin->setSettingsRepository($settingsRepository);
    $plugin->setWriteApiClient($writeApi);
    $plugin->setLogisticsOrderFactory($logisticsOrderFactory);
    $plugin->setFailedRequestRepository(new FailedRequestRepository());
    $plugin->initialize();
});

register_activation_hook(__FILE__, [Plugin::class, "onActivation"]);
register_uninstall_hook(__FILE__, [Plugin::class, "onUninstall"]);

if (!function_exists('env')) {
    /**
     * Retrieves variable value from .env or default value.
     *
     * @param string $varName the name of the variable defined in the .env.
     * @param string $defaultValue default value to be returned if variable not found.
     * @return string
     */
    function env(string $varName, string $defaultValue = null): string
    {
        return array_key_exists($varName, $_ENV) ? $_ENV[$varName] : $defaultValue;
    }
}
