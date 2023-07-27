<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\OrderRepository;


test('it can find an order', function () {
    $repository = new OrderRepository();

    $order = new \WC_Order();
    $order->save();

    $order = $repository->findById($order->get_id());

    expect($order)->not->toBeNull();
});

test('it returns null if the order was not found', function () {
    $repository = new OrderRepository();

    $order = $repository->findById(42);

    expect($order)->toBeNull();
});
