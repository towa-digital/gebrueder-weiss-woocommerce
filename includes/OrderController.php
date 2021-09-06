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

use Towa\GebruederWeissSDK\Model\InlineObject1 as SuccessCallbackBody;
use Towa\GebruederWeissSDK\Model\InlineObject2 as FulfilledCallbackBody;
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
        $requestBody = $request->get_body();
        $data = new SuccessCallbackBody(json_decode($requestBody, true));

        $order = $this->orderRepository->findById($id);

        if (is_null($order)) {
            return new WP_REST_Response(null, 404, null);
        }

        $order->update_meta_data($this->settings->getOrderIdFieldName(), $data->getOrderId());

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
        $requestBody = $request->get_body();
        $data = new FulfilledCallbackBody(json_decode($requestBody, true));

        $order = $this->orderRepository->findById($id);

        if (is_null($order)) {
            return new WP_REST_Response(null, 404, null);
        }

        $order->set_status($this->settings->getFulfilledState());
        $order->update_meta_data($this->settings->getTrackingLinkFieldName(), $data->getTrackingUrl());
        $order->update_meta_data($this->settings->getCarrierInformationFieldName(), $data->getTransportProduct());

        $order->save();

        return new WP_REST_Response(null, 200, null);
    }
}
