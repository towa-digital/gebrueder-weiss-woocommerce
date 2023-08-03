<?php
/**
 * Shipping Method for Gebrüder Weiss
 *
 * @package Plugin
 */

namespace Towa\GebruederWeissWooCommerce\ShippingMethods;

use Towa\GebruederWeissWooCommerce\Plugin;

/**
 * Shipping Method for Gebrüder Weiss
 */
class GBWShippingMethod extends \WC_Shipping_Method
{
    const SHIPPING_METHOD_ID = 'gbw_shipping';

    const WAREHOUSE_ID_KEY = 'gbwWarehouseID';

    /**
     * Constructor for your shipping class
     *
     * @param int $instance_id ID of the instance, used for settings.
     */
    public function __construct(int $instance_id = 0)
    {
        parent::__construct($instance_id);

        // Id for your shipping method. Should be uunique.
        $this->id                 =  self::SHIPPING_METHOD_ID;

        // Title shown in admin.
        $this->method_title       = __('GBW Shipping', Plugin::LANGUAGE_DOMAIN);

        // Description shown in admin.
        $this->method_description = __('Shipping with Gebrüder Weiss', Plugin::LANGUAGE_DOMAIN);

        $this->enabled            = "yes";
        $this->title              = "Gebrüder Weiss";
        $this->supports           = array(
                                        'settings',
                                        'shipping-zones',
                                        'instance-settings',
                                        'instance-settings-modal'
                                    );

        $this->init();
    }

    /**
     * Init your settings
     */
    private function init()
    {
        // Load the settings API.
        $this->initInstanceFormFields();

        // This is part of the settings API. Loads settings you previously init.
        $this->init_settings();
    }

    /**
     * Instantiates the form fields for the instance settings
     */
    public function initInstanceFormFields(): void
    {
        $this->instance_form_fields = array(
            self::WAREHOUSE_ID_KEY => array (
                'title' => __('GBW Warehouse ID', Plugin::LANGUAGE_DOMAIN),
                'type' => 'text',
                'description' => __('The GBW Warehouse Id. If not supplied the default will be used, assigned to your account', Plugin::LANGUAGE_DOMAIN),
            )
        );
    }

    /**
     * Calculate the shipping rate.
     *
     * @param array $package The package to calculate the shipping for.
     */
    public function calculate_shipping($package = array()): void
    {
        $rate = array(
            'label' => $this->title,
            'cost' => '0',
            'calc_tax' => 'per_item'
        );

        // Register the rate.
        $this->add_rate($rate);
    }

    /**
     * Return the warehouse id, set on the instance
     */
    public function getWareHouseID(): ?string
    {
        return $this->get_option(self::WAREHOUSE_ID_KEY);
    }

    public static function getShippingMethodId(): string
    {
        return self::SHIPPING_METHOD_ID;
    }
}
