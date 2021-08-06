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

    public function test_it_does_update_the_woocommerce_order_status()
    {
        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows(['getFulfilledState' => 'wc-fulfilled']);

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("get_status");
        $order->allows("save");

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => $order
        ]);

        $controller = new OrderController($settingsRepository, $orderRepository);


        $controller->handleOrderUpdateRequest($request);


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

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => null
        ]);

        $controller = new OrderController($settingsRepository, $orderRepository);


        $response = $controller->handleOrderUpdateRequest($request);


        $this->assertEquals(404, $response->status);
    }

    public function test_it_returns_a_200_response_if_a_order_with_the_given_id_exists()
    {
        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows(['getFulfilledState' => 'wc-fulfilled']);

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("get_status");
        $order->allows("save");

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => $order
        ]);

        /** @var \WP_REST_Request|MockInterface */
        $request = Mockery::mock(\WP_REST_Request::class);
        $request->allows('get_params')->andReturn(['id' => 12]);

        $controller = new OrderController($settingsRepository, $orderRepository);


        $response = $controller->handleOrderUpdateRequest($request);


        $this->assertEquals(200, $response->status);
    }
}
