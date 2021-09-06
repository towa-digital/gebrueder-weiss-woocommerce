<?php
/**
 * Order Controller
 *
 * Used to provide the callback endpoint & handling.
 *
 * @package Plugin
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

use WP_REST_Request;
use WP_REST_Response;

/**
 * OptionsPage Class
 */
class OrderController
{
    private const NAMESPACE = 'gebrueder-weiss-woocommerce/v1';

    /**
     * The settings used by the OrderController instance.
     *
     * @var SettingsRepository
     */
    private $settings = null;

    /**
     * WooCommerce Order Repository
     *
     * @var OrderRepository
     */
    private $orderRepository = null;

    /**
     * Constructor.
     *
     * @param SettingsRepository $settings the states.
     * @param OrderRepository    $orderRepository WooCommerce Order Repository.
     */
    public function __construct(SettingsRepository $settings, OrderRepository $orderRepository)
    {
        $this->settings = $settings;
        $this->orderRepository = $orderRepository;

        \add_action('rest_api_init', function () {
            register_rest_route(self::NAMESPACE, '/orders/(?P<id>\d+)/callbacks/success', [
                'methods' => 'POST',
                'callback' => [$this, 'handleOrderUpdateRequest'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    /**
     * The callback handler.
     *
     * @param WP_REST_Request $request the post request.
     */
    public function handleOrderUpdateRequest(WP_REST_Request $request): WP_REST_Response
    {
        $id = $request->get_params()['id'];

        $order = $this->orderRepository->findById($id);

        if (is_null($order)) {
            return new WP_REST_Response(null, 404, null);
        }

        $order->set_status($this->settings->getFulfilledState());
        $order->save();

        return new WP_REST_Response(null, 200, null);
    }
}
