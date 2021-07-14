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
 * Plugin URI: https://www.github.com/towa-digital/gbw-woocommerce-plugin.git
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

// Include the main WooCommerce class.
if (!class_exists('GbWeiss', false)) {
    include_once dirname(__FILE__) . '/includes/GbWeiss.php';
}

/**
 * Retrieve an instance of the plugin.
 */
function GbWeiss()
{
    return GbWeiss\includes\GbWeiss::instance();
}

add_action("init", function () {
    if (!GbWeiss\includes\GbWeiss::checkPluginCompatabilityAndPrintErrorMessages()) {
        return;
    };

    // Initialize the plugin here.
});
