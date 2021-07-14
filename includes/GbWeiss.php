<?php
/**
 * GbWeiss Setup
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
        $this->init_hooks();
    }

    public static function instance()
    {
        if (is_null(self::$instance) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init_hooks()
    {

    }

    public static function checkPluginCompatabilityAndPrintErrorMessages(): bool
    {
        if (!self::pluginIsCompatibleWithCurrentPhpVersion()) {
            $errorMessage = "Gebrüder Weiss WooCommerce is not compatible with PHP ".phpversion().".";
            self::showWordpressAdminErrorMessage($errorMessage);
            return false;
        }

        if (!self::isWooCommerceActive()) {
            $errorMessage = "Gebrüder Weiss WooCommerce requires WooCommerce to be installed.";
            self::showWordpressAdminErrorMessage($errorMessage);
            return false;
        }

        return true;
    }

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

    private static function isWooCommerceActive(): bool
    {
        // as recommended by WooCommerce https://docs.woocommerce.com/document/create-a-plugin/
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    private static function showWordpressAdminErrorMessage(string $message): void
    {
        add_action(
            "admin_notices", function () use ($message) {
                ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo $message ?></p>
                    </div>
                <?php
            }
        );
    }
}
