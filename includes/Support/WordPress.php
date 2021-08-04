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
    public static function sendErrorNotificationToAdmin(string $subject, string $message): void
    {
    }
}
