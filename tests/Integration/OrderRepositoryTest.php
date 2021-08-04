<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\Exceptions\OrderNotFoundException;
use Towa\GebruederWeissWooCommerce\OrderRepository;

class OrderRepositoryTest extends \WP_UnitTestCase
{
    public function test_it_can_find_an_order()
    {
        $repository = new OrderRepository();

        $order = new \WC_Order();
        $order->save();

        $order = $repository->findById($order->get_id());

        $this->assertNotNull($order);
    }

    public function test_it_throws_an_exception_if_the_order_was_not_found()
    {
        $this->expectException(OrderNotFoundException::class);

        $repository = new OrderRepository();

        $repository->findById(42);
    }
}
