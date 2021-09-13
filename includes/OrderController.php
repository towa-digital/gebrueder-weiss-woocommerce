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
                'callback' => [$this, 'handleSuccessCallback'],
                'permission_callback' => '__return_true'
            ]);

            register_rest_route(self::NAMESPACE, '/orders/(?P<id>\d+)/callbacks/fulfillment', [
                'methods' => 'POST',
                'callback' => [$this, 'handleFulfillmentCallback'],
                'permission_callback' => '__return_true'
            ]);
        });
    }

    /**
     * The callback handler for success callbacks.
     *
     * @param WP_REST_Request $request the post request.
     */
    public function handleSuccessCallback(WP_REST_Request $request): WP_REST_Response
    {
        $id = $request->get_params()['id'];
        $data = json_decode($request->get_body());

        $order = $this->orderRepository->findById($id);

        if (is_null($order)) {
            return new WP_REST_Response(null, 404, null);
        }

        if (empty($data)) {
            return new WP_REST_Response(['message' => 'Invalid payload'], 422);
        }

        if (empty($data->orderId)) {
            return new WP_REST_Response(['message' => 'Missing orderId'], 422);
        }

        $order->update_meta_data($this->settings->getOrderIdFieldName(), $data->orderId);

        $order->save();

        return new WP_REST_Response(null, 200, null);
    }

    /**
     * The callback handler for fulfillment callbacks.
     *
     * @param WP_REST_Request $request The post request.
     * @return WP_REST_Response
     */
    public function handleFulfillmentCallback(WP_REST_Request $request): WP_REST_Response
    {
        $id = $request->get_params()['id'];
        $data = json_decode($request->get_body());

        $order = $this->orderRepository->findById($id);

        if (is_null($order)) {
            return new WP_REST_Response(null, 404, null);
        }

        if (empty($data)) {
            return new WP_REST_Response(['message' => 'Invalid payload'], 422);
        }

        if (empty($data->trackingUrl)) {
            return new WP_REST_Response(['message' => 'Missing trackingUrl'], 422);
        }

        if (empty($data->transportProduct)) {
            return new WP_REST_Response(['message' => 'Missing trackingProduct'], 422);
        }

        $order->set_status($this->settings->getFulfilledState());
        $order->update_meta_data($this->settings->getTrackingLinkFieldName(), $data->trackingUrl);
        $order->update_meta_data($this->settings->getCarrierInformationFieldName(), $data->transportProduct);

        $order->save();

        return new WP_REST_Response(null, 200, null);
    }
}
