<?php
/**
 * WooCommerce Order Repository
 *
 * @package Plugin
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

use Exception;

/**
 * WooCommerce Order Repository
 */
class OrderRepository
{
    /**
     * Finds WoCommerce Orders by their id.
     *
     * @param integer $id WooCommerce Order id.
     * @return \WC_Order|null
     */
    public function findById(int $id): ?\WC_Order
    {
        try {
            return new \WC_Order($id);
        } catch (Exception $e) {
            return null;
        }
    }
}
