<?php
/**
 * Gebrüder Weiss Woocommere
 *
 * @package GbWeiss
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

use GbWeiss\includes\GbWeiss;
use GbWeiss\includes\OAuth\OAuthAuthenticator;
use GbWeiss\includes\OrderStateRepository;
use GbWeiss\includes\SettingsRepository;
use League\OAuth2\Client\Provider\GenericProvider;
use Towa\GebruederWeissSDK\Api\WriteApi;

/**
 * Use Dotenv to set required environment variables and load .env file in root
 */
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

/**
 * If the gbw_request_retry_queue table does not exist (first activation) it gets created.
 *
 * @return void
 */
function onActivation()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}gbw_request_retry_queue` (
      orderID int NOT NULL,
      status varchar(50) NOT NULL,
      counter int UNSIGNED NOT NULL,
      PRIMARY KEY  (orderID)
    ) $charset_collate;";
    $wpdb->query($sql);
}

/**
 * When the plugin gets uninstalled the gbw_request_retry_queue table is dropped.
 *
 * @return void
 */
function onUninstall()
{
    global $wpdb;
    $sql = "DROP TABLE IF EXISTS `{$wpdb->base_prefix}gbw_request_retry_queue`";
    $wpdb->query($sql);
}

register_activation_hook(__FILE__, 'onActivation');
register_uninstall_hook(__FILE__, 'onUninstall');

add_action("init", function () {
    if (!GbWeiss::checkPluginCompatabilityAndPrintErrorMessages()) {
        return;
    };

    $plugin = GbWeiss::getInstance();

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

    $plugin->setAuthenticationClient($authenticationClient);
    $plugin->setOrderStateRepository(new OrderStateRepository());
    $plugin->setSettingsRepository(new SettingsRepository());
    $plugin->setWriteApiClient($writeApi);
    $plugin->initialize();
});

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
