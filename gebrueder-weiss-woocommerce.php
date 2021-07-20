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

add_action("init", function () {
    if (!GbWeiss::checkPluginCompatabilityAndPrintErrorMessages()) {
        return;
    };
    $plugin = GbWeiss::getInstance();
    $plugin->setOrderStateRepository(new OrderStateRepository());
    $plugin->initialize();
    $authenticationClient = new OAuthAuthenticator(new GuzzleHttp\Client());
    $authenticationClient->setAuthenticationEndpoint('http://18019fdce8ff:8887/token');
    $plugin->setAuthenticationClient($authenticationClient);
});

add_action("admin_init", function () {
    $plugin = GbWeiss::getInstance();
    $plugin->showErrorMessageIfSelectedOrderStatesDoNotExist();
});
