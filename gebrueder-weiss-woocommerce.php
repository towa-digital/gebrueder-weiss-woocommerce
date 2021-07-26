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
use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Use Dotenv to set required environment variables and load .env file in root
 */
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

add_action("init", function () {
    if (!GbWeiss::checkPluginCompatabilityAndPrintErrorMessages()) {
        return;
    };

    $plugin = GbWeiss::getInstance();


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


    $plugin->setAuthenticationClient($authenticationClient);
    $plugin->setOrderStateRepository(new OrderStateRepository());
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
