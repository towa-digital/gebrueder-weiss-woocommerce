<?php
/**
 * Singleton Class
 *
 * Parent class used to instatiate Singletons
 *
 * @package Support
 */

namespace Towa\GebruederWeissWooCommerce\Support;

defined('ABSPATH') || exit;

/**
 * Can be extended for Custom Singletons
 */
class Singleton
{
    /**
     * Singleton Instance
     *
     * @var array
     */
    private static $instances = [];

    /**
     * Singleton's constructor should not be public. However, it can't be
     * private either if we want to allow subclassing.
     */
    protected function __construct()
    {
    }

    /**
     * Cloning and unserialization are not permitted for singletons.
     */
    protected function __clone()
    {
    }

    /**
     * Wakeup will throw exception
     *
     * @throws \Exception If tried to unserialize.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Get the Instance of the Singleton.
     */
    public static function getInstance()
    {
        $subclass = static::class;
        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static();
        }
        return self::$instances[$subclass];
    }
}
