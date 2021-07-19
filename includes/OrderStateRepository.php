<?php
/**
 * Repository for WooCommerce Order States
 *
 * @package GbWeissOptions
 */

namespace GbWeiss\includes;

/**
 * Repository for WooCommerce Order Statuses
 */
class OrderStateRepository
{
    /**
     * Returns all order statuses available in WooCommerce.
     *
     * @return array
     */
    public function getAllOrderStates(): array
    {
        return \wc_get_order_statuses();
    }
}
