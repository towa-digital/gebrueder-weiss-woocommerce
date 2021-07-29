<?php
/**
 * Factory for creating Logistics Orders
 *
 * @package GbWeiss
 */

namespace GbWeiss\includes;

defined('ABSPATH') || exit;

use Towa\GebruederWeissSDK\Model\LogisticsOrder;

/**
 * Factory for creating Logistics Orders
 */
class LogisticsOrderFactory
{
    /**
     * Creates a logistics order from a WooCommerce order
     *
     * @param object $wooCommerceOrder The order to be converted into a logistics order.
     * @return LogisticsOrder
     */
    public static function buildFromWooCommerceOrder(object $wooCommerceOrder): LogisticsOrder
    {
        return new LogisticsOrder();
    }
}
