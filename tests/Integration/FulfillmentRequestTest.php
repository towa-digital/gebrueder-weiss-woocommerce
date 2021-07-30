<?php

namespace Tests\Integration;

use GbWeiss\includes\OrderController;
use GbWeiss\includes\SettingsRepository;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class FulfillmentRequestTest extends \WP_UnitTestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_does_update_the_woocommerce_order_status()
    {
        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows(['getFulfilledState' => 'wc-fulfilled']);

        /** @var MockInterface|WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("get_status");
        $order->allows("save");

        $controller = new OrderController($settingsRepository);


        $controller->updateOrderStatus($order, 'wc-fulfilled');


        $order->shouldHaveReceived('set_status', ['wc-fulfilled']);
        $order->shouldHaveReceived('save');
    }

    public function test_it_returns_a_404_response_if_no_order_with_the_given_id_exists()
    {
        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows();

        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);

        $controller = new OrderController($settingsRepository);


        $response = $controller->handleOrderUpdateRequest($request);


        $this->assertEquals(404, $response->status);
    }

    public function test_it_returns_on_callback_with_200()
    {
        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows(['getFulfilledState' => 'wc-fulfilled']);

        $order = wc_create_order();

        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => $order->get_id()]);

        $controller = new OrderController($settingsRepository);


        $response = $controller->handleOrderUpdateRequest($request);


        $this->assertEquals(200, $response->status);
    }
}
