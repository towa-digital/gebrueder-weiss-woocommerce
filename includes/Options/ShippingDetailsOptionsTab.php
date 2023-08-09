<?php
/**
 * Shipping Details Options Tab
 *
 * @package Options
 */

namespace Towa\GebruederWeissWooCommerce\Options;

defined('ABSPATH') || exit;

use Towa\GebruederWeissWooCommerce\Plugin;

/**
 * Shipping Details Options Tab
 */
class ShippingDetailsOptionsTab extends Tab
{
    const USE_GBW_SHIPPING_ZONES_KEY = 'useGbwShippingZones';

    /**
     * Creates the shipping details options.
     */
    public function __construct()
    {
        parent::__construct(__('Shipping Details', Plugin::LANGUAGE_DOMAIN), 'shipping-details');

        $this->addOption(new Option(
            'Use GBW Shipping Zones',
            self::USE_GBW_SHIPPING_ZONES_KEY,
            __('If enabled, only Orders with the Gebrüder Weiss shipping method will be processed by Gebrüder Weiss.
            For further information please consult the documentation', Plugin::LANGUAGE_DOMAIN),
            'shipping-details',
            'boolean',
            null,
            false
        ));
    }
}
