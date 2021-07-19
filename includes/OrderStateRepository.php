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

    /**
     * Returns the order state with the given slug.
     *
     * @param string $slug The slug associated to order state.
     * @return array|null
     */
    public function getOrderStateBySlug(string $slug): ?array
    {
        $orderStates = $this->getAllOrderStates();

        if (!key_exists($slug, $orderStates)) {
            return null;
        }

        return [ "slug" => $slug, "display_name" => $orderStates[$slug]];
    }
}
