<?php

namespace Tests\Unit;

use Towa\GebruederWeissWooCommerce\OrderController;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Towa\GebruederWeissWooCommerce\OrderRepository;

class OrderControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_the_success_callback_returns_a_404_response_if_no_order_with_the_given_id_exists()
    {
        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows();

        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);
        $request->allows('get_body')->andReturn('{"order_id": 12}');

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => null
        ]);

        $controller = new OrderController($settingsRepository, $orderRepository);


        $response = $controller->handleSuccessCallback($request);


        $this->assertEquals(404, $response->status);
    }

    public function test_the_success_callback_returns_a_200_response_if_a_order_with_the_given_id_exists()
    {
        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            'getFulfilledState' => 'wc-fulfilled',
            'getOrderIdFieldName' => 'order_id'
        ]);

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows("get_status");
        $order->allows("update_meta_data");
        $order->allows("save");

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => $order
        ]);

        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);
        $request->allows('get_body')->andReturn('{"order_id": 12, "tracking_url": "http://example.com", "transport_product": "DHL"}');

        $controller = new OrderController($settingsRepository, $orderRepository);

        $response = $controller->handleSuccessCallback($request);

        $this->assertEquals(200, $response->status);
    }

    public function test_the_success_callback_updates_the_order_id()
    {
        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            'getFulfilledState' => 'wc-fulfilled',
            'getOrderIdFieldName' => 'order_id'
        ]);

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows("get_status");
        $order->allows("update_meta_data");
        $order->allows("save");

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => $order
        ]);

        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);
        $request->allows('get_body')->andReturn('{"order_id": 13, "tracking_url": "http://example.com", "transport_product": "DHL"}');

        $controller = new OrderController($settingsRepository, $orderRepository);

        $controller->handleSuccessCallback($request);

        $order->shouldHaveReceived("update_meta_data")->with("order_id", 13);
        $order->shouldHaveReceived("save");
    }

    public function test_the_fulfillment_callback_updates_the_woocommerce_order_status()
    {
        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);
        $request->allows('get_body')->andReturn('{"order_id": 12, "tracking_url": "http://example.com", "transport_product": "DHL"}');

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            'getFulfilledState' => 'wc-fulfilled',
            'getTrackingLinkFieldName' => 'tracking_id',
            'getCarrierInformationFieldName' => 'carrier_information'
        ]);

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("get_status");
        $order->allows("update_meta_data");
        $order->allows("save");

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => $order
        ]);

        $controller = new OrderController($settingsRepository, $orderRepository);

        $controller->handleFulfillmentCallback($request);

        $order->shouldHaveReceived("set_status")->with("wc-fulfilled");
        $order->shouldHaveReceived('save');
    }

    public function test_the_fulfillment_callback_updates_the_order_meta_data()
    {
        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);
        $request->allows('get_body')->andReturn('{"order_id": 12, "tracking_url": "http://example.com", "transport_product": "DHL"}');

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            'getFulfilledState' => 'wc-fulfilled',
            'getTrackingLinkFieldName' => 'tracking_id',
            'getCarrierInformationFieldName' => 'carrier_information'
        ]);

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("get_status");
        $order->allows("update_meta_data");
        $order->allows("save");

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => $order
        ]);

        $controller = new OrderController($settingsRepository, $orderRepository);

        $controller->handleFulfillmentCallback($request);

        $order->shouldHaveReceived("update_meta_data")->with("tracking_id", "http://example.com");
        $order->shouldHaveReceived("update_meta_data")->with("carrier_information", "DHL");
    }

    public function test_the_fulfillment_callback_returns_200_on_success()
    {
        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);
        $request->allows('get_body')->andReturn('{"order_id": 12, "tracking_url": "http://example.com", "transport_product": "DHL"}');

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            'getFulfilledState' => 'wc-fulfilled',
            'getTrackingLinkFieldName' => 'tracking_id',
            'getCarrierInformationFieldName' => 'carrier_information'
        ]);

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("get_status");
        $order->allows("update_meta_data");
        $order->allows("save");

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => $order
        ]);

        $controller = new OrderController($settingsRepository, $orderRepository);

        $response = $controller->handleFulfillmentCallback($request);

        $this->assertEquals(200, $response->get_status());
    }

    public function test_the_fulfillment_callback_returns_404_if_the_order_cannot_be_found()
    {
        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);
        $request->allows('get_body')->andReturn('{"order_id": 12, "tracking_url": "http://example.com", "transport_product": "DHL"}');

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => null
        ]);

        $controller = new OrderController($settingsRepository, $orderRepository);

        $response = $controller->handleFulfillmentCallback($request);

        $this->assertEquals(404, $response->get_status());
    }
}
