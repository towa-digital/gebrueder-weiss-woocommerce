<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\OrderStateRepository;
use Mockery;
use Mockery\MockInterface;
use Towa\GebruederWeissWooCommerce\OrderRepository;

class PluginInitializationTest extends \WP_UnitTestCase
{
    public function test_it_registers_an_init_hook()
    {
        global $wp_filter;
        $numberOfInitFiltersBeforeRegistration = count($wp_filter['init'][10]);
        include(dirname(__FILE__) . "/../../gebrueder-weiss-woocommerce.php");
        $numberOfInitFiltersAfterRegistration = count($wp_filter['init'][10]);

        $this->assertSame(1, $numberOfInitFiltersAfterRegistration - $numberOfInitFiltersBeforeRegistration);
    }

    public function test_it_adds_an_admin_notice_if_woocommerce_is_not_installed()
    {
        global $wp_filter;
        $numberOfInitFiltersBeforeCheck = count($wp_filter['admin_notices'][10]);
        Plugin::checkPluginCompatabilityAndPrintErrorMessages();
        $numberOfInitFiltersAfterCheck = count($wp_filter['admin_notices'][10]);

        $this->assertSame(1, $numberOfInitFiltersAfterCheck - $numberOfInitFiltersBeforeCheck);
    }

    public function test_it_does_not_pass_compatibility_check_without_woocommerce_active()
    {
        $this->assertFalse(Plugin::checkPluginCompatabilityAndPrintErrorMessages());
    }

    public function test_it_does_pass_the_compatibility_check_with_woocommerce_active()
    {
        update_option("active_plugins", ["woocommerce/woocommerce.php"]);
        $this->assertTrue(Plugin::checkPluginCompatabilityAndPrintErrorMessages());
    }

    public function test_it_registers_an_action_for_woocommerce_order_status()
    {
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
        $this->assertSame(1, $numberOfInitFiltersAfterCheck);
    }
}
