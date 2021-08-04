<?php
/**
 * WooCommerce Order Repository
 *
 * @package Plugin
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

use Exception;
use Towa\GebruederWeissWooCommerce\Exceptions\OrderNotFoundException;

/**
 * WooCommerce Order Repository
 */
class OrderRepository
{
    /**
     * Finds WoCommerce Orders by their id.
     *
     * @param integer $id WooCommerce Order id.
     * @return \WC_Order
     * @throws OrderNotFoundException Thrown if no order with the passed id was found.
     */
    public function findById(int $id): \WC_Order
    {
        try {
            return new \WC_Order($id);
        } catch (Exception $e) {
            throw new OrderNotFoundException();
        }
    }
}
