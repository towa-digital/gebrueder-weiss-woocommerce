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

/**
 * Main GbWeiss class
 */
final class GbWeiss
{
    /**
     * The single instance of the class.
     *
     * @var GbWeiss
     */
    protected static $instance = null;

    /**
     * Initialize GbWeiss Plugin
     */
    public function __construct()
    {
        $this->initHooks();
    }

    /**
     * Returns the singleton instance for the GbWeiss class.
     */
    public static function instance(): GbWeiss
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initializes the plugin
     *
     * @return void
     */
    public function initHooks(): void
    {
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
        add_action(
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
}
