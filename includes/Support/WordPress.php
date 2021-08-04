<?php
/**
 * Helper Class for interacting with WordPress
 *
 * @package Plugin
 */

namespace Towa\GebruederWeissWooCommerce\Support;

defined('ABSPATH') || exit;

/**
 * Helper Class for interacting with WordPress
 */
class WordPress
{
    /**
     * Sends an error notification to the WordPress administrator
     *
     * @param string $subject Subject for the message.
     * @param string $message Message body.
     * @return void
     */
    public static function sendMailToAdmin(string $subject, string $message): void
    {
        \wp_mail(WordPress::getOption("admin_email"), $subject, $message);
    }

    /**
     * Reads WordPressOptions
     *
     * @param string $name The name of the option.
     * @return mixed
     */
    public static function getOption(string $name)
    {
        return \get_option($name, null);
    }

    /**
     * Updates a WordPress option
     *
     * @param string $name Name of the option.
     * @param mixed  $value New value for the option.
     * @return void
     */
    public static function updateOption(string $name, $value): void
    {
        \update_option($name, $value);
    }

    /**
     * Reads the wordpress site URL from the options.
     *
     * @return string|null
     */
    public static function getSiteUrl(): ?string
    {
        return \get_site_url();
    }
}
