<?php
/**
 * Order Controller
 *
 * Used to manipulate WooCommerce Orders.
 *
 * @package GbWeiss
 */

namespace GbWeiss\includes;

use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * OptionsPage Class
 */
class OrderController
{
    private const NAMESPACE = 'gebrueder-weiss-woocommerce';

    /**
     * The settings used by the OrderController instance.
     *
     * @var SettingsRepository
     */
    private $settings = null;


    /**
     * Constructor.
     *
     * @param SettingsRepository $settings the states.
     */
    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
        \add_action('rest_api_init', function () {
            register_rest_route(self::NAMESPACE, '/update/(?P<id>\d+)', array(
                'methods' => 'POST',
                'callback' => array($this, 'handleCallback')
            ));
        });
    }

    /**
     * The callback handler.
     *
     * @param \WP_REST_Request $request the post request.
     */
    public function handleCallback(\WP_REST_Request $request): WP_REST_Response
    {
        try {
            $id = $request->get_params()['id'];
            $order = new \WC_order($id);
            $this->updateOrderStatus($order, $this->settings->getFulfilledState());
        } catch (\Exception $e) {
            return new \WP_REST_Response(null, 404, null);
        }
        return new WP_REST_Response(null, 200, null);
    }

    /**
     * Sets the new WooCommerce Order Status.
     *
     * @param \WC_Order $order the woo commerce order.
     * @param string    $status the new order status.
     * @return void
     */
    private function updateOrderStatus(\WC_Order $order, string $status)
    {
        $order->set_status($status);
        $order->save();
    }
}
