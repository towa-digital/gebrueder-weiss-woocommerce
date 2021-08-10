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

    /**
     * Adds a cron interval
     *
     * @param string  $key   Key for the interval.
     * @param integer $intervalInSeconds    Number of seconds between executions.
     * @param string  $displayName   Display name.
     * @return void
     */
    public static function addCronInterval(string $key, int $intervalInSeconds, string $displayName): void
    {
        \add_filter("cron_schedules", function ($schedules) use ($key, $intervalInSeconds, $displayName) {
            $schedules[$key] = [
                "interval" => $intervalInSeconds,
                "display"  => $displayName
            ];

            return $schedules;
        });
    }

    /**
     * Schedules a cronjob if there is no schedule for the hook yet.
     *
     * @param string  $hook Name of the hook.
     * @param integer $nextExecutionTimestamp Unix timestamp for the next execution.
     * @param string  $schedule Name for the schedule used for execution of the hook.
     * @return void
     */
    public static function scheduleCronjob(string $hook, int $nextExecutionTimestamp, string $schedule): void
    {
        if (\wp_get_schedule($hook)) {
            return;
        }

        \wp_schedule_event($nextExecutionTimestamp, $schedule, $hook);
    }

    /**
     * Adds an action for a cronjob.
     *
     * @param string   $hook Name of the hook.
     * @param callable $callable Function to execute.
     * @return void
     */
    public static function addCronjobAction(string $hook, callable $callable): void
    {
        \add_action($hook, $callable, 10, 1);
    }

    /**
     * Clears a scheduled hook.
     *
     * @param string $hook Name of the scheduled hook.
     * @return void
     */
    public static function clearScheduledHook(string $hook): void
    {
        \wp_clear_scheduled_hook($hook);
    }
}
