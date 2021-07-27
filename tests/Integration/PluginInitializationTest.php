<?php

namespace Tests\Integration;

use GbWeiss\includes\GbWeiss;
use GbWeiss\includes\OrderStateRepository;
use Mockery;

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
        GbWeiss::checkPluginCompatabilityAndPrintErrorMessages();
        $numberOfInitFiltersAfterCheck = count($wp_filter['admin_notices'][10]);

        $this->assertSame(1, $numberOfInitFiltersAfterCheck - $numberOfInitFiltersBeforeCheck);
    }

    public function test_it_does_not_pass_compatibility_check_without_woocommerce_active()
    {
        $this->assertFalse(GbWeiss::checkPluginCompatabilityAndPrintErrorMessages());
    }

    public function test_it_does_pass_the_compatibility_check_with_woocommerce_active()
    {
        update_option("active_plugins", ["woocommerce/woocommerce.php"]);
        $this->assertTrue(GbWeiss::checkPluginCompatabilityAndPrintErrorMessages());
    }

    public function test_it_registers_an_action_for_woocommerce_order_status()
    {
        global $wp_filter;
        $mock = Mockery::mock(OrderStateRepository::class);
        $mock->shouldReceive("getAllOrderStates")->andReturn([]);

        /** @var GbWeiss */
        $plugin = GbWeiss::getInstance();
        $plugin->setOrderStateRepository($mock);
        $plugin->initialize();

        $numberOfInitFiltersAfterCheck = count($wp_filter['woocommerce_order_status_changed'][10]);
        $this->assertSame(1, $numberOfInitFiltersAfterCheck);
    }
}
