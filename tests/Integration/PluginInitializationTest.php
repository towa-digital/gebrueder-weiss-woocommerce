<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\OrderStateRepository;
use Mockery\MockInterface;
use Towa\GebruederWeissWooCommerce\OrderRepository;


test('it registers an init hook', function () {
    global $wp_filter;
    $numberOfInitFiltersBeforeRegistration = count($wp_filter['init'][10]);
    include(dirname(__FILE__) . "/../../gebrueder-weiss-woocommerce.php");
    $numberOfInitFiltersAfterRegistration = count($wp_filter['init'][10]);

    expect($numberOfInitFiltersAfterRegistration - $numberOfInitFiltersBeforeRegistration)->toBe(1);
});

test('it adds an admin notice if woocommerce is not installed', function () {
    global $wp_filter;
    $numberOfInitFiltersBeforeCheck = count($wp_filter['admin_notices'][10]);
    Plugin::checkPluginCompatabilityAndPrintErrorMessages();
    $numberOfInitFiltersAfterCheck = count($wp_filter['admin_notices'][10]);

    expect($numberOfInitFiltersAfterCheck - $numberOfInitFiltersBeforeCheck)->toBe(1);
});

test('it does not pass compatibility check without woocommerce active', function () {
    expect(Plugin::checkPluginCompatabilityAndPrintErrorMessages())->toBeFalse();
});

test('it does pass the compatibility check with woocommerce active', function () {
    update_option("active_plugins", ["woocommerce/woocommerce.php"]);
    expect(Plugin::checkPluginCompatabilityAndPrintErrorMessages())->toBeTrue();
});

test('it registers an action for woocommerce order status', function () {
    global $wp_filter;

    /** @var MockInterface|OrderStateRepository */
    $orderStateRepository = Mockery::mock(OrderStateRepository::class);
    $orderStateRepository->shouldReceive("getAllOrderStates")->andReturn([]);

    /** @var MockInterface|OrderRepository */
    $orderRepository = Mockery::mock(OrderRepository::class);

    /** @var Plugin */
    $plugin = Plugin::getInstance();
    $plugin->setOrderStateRepository($orderStateRepository);
    $plugin->setOrderRepository($orderRepository);
    $plugin->initialize();

    $numberOfInitFiltersAfterCheck = count($wp_filter['woocommerce_order_status_changed'][10]);
    expect($numberOfInitFiltersAfterCheck)->toBe(1);
});

test('it registers an action for the retry requests cron job', function () {
    global $wp_filter;

    /** @var Plugin */
    $plugin = Plugin::getInstance();
    $plugin->initialize();

    expect(count($wp_filter[Plugin::RETRY_REQUESTS_CRON_JOB][10]))->toBe(1);
});
