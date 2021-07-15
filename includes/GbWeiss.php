<?php
/**
 * GbWeiss Setup
 */
namespace GbWeiss\includes;

use Towa\GebruederWeissSDK\{Configuration, Api\ReadApi};


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

    public function init_hooks()
    {

    }

    public static function instance()
    {
        if (is_null(self::$instance) ) {
            self::$instance = new self();
        }

        $config = new Configuration();

        
        return self::$instance;
    }
}
