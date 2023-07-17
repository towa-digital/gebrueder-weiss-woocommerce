<?php
/**
 * Transient Helper
 *
 * Helper Class for interacting with WordPress Transients
 *
 * @package Support
 */

namespace Towa\GebruederWeissWooCommerce\Support;

/**
 * Helper Class for interacting with WordPress Transients
 * It Avoids using magic strings for the transient keys and having to escape them manually.
 */
class Transient
{
    public const META_KEYS = 'gbw_meta_keys';

    public const META_KEY_TIME_IN_SECONDS = 24 * 60 * 60;

    /**
     * Fetches a transient value from the database, if it does not exist, it will be created.
     * The Callable will be called and its return value saved as the transient.
     *
     * @param string   $transientKey The key of the transient.
     * @param callable $callback The callback to be called if the transient does not exist.
     * @param mixed    $callbackArgs The callback Args that should be passed to the callback function.
     * @param int      $timeInSeconds The time in seconds the transient should be valid.
     */
    public static function getTransient(string $transientKey, callable $callback, $callbackArgs, int $timeInSeconds): mixed
    {
        $transientValue = get_transient(esc_sql($transientKey));

        if ($transientValue === false) {
            $transientValue = esc_sql(call_user_func($callback, $callbackArgs));
            set_transient($transientKey, $transientValue, $timeInSeconds);
        }

        return $transientValue;
    }

    /**
     * Deletes given Transient Key.
     *
     * @param string $transientKey The key of the transient.
     */
    public static function deleteTransient(string $transientKey): void
    {
        delete_transient($transientKey);
    }
}
