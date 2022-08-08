<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\Options\Option;
use Towa\GebruederWeissWooCommerce\Options\OrderOptionsTab;
use Towa\GebruederWeissWooCommerce\Plugin;


class PluginInstallationTest extends \WP_UnitTestCase
{
    public function test_it_creates_the_request_queue_table()
    {
        global $wpdb;

        // Prevent errors from being dumped into the console. They will be shown when the assertion fails.
        $wpdb->suppress_errors();

        Plugin::onActivation();

        /**
         * The WordPress tables forces all tables created during a test to be a temporary table.
         * Hence, the table will not be listed in the output of "show table" or
         * similar statements. As an alternative we try to execute a query
         * against the table and check if there was an error.
         */
        $wpdb->get_var("select count(*) from {$wpdb->prefix}gbw_request_retry_queue");
        $this->assertEmpty($wpdb->last_error);
    }

    public function test_it_adds_the_retry_requests_cron_job()
    {
        Plugin::onActivation();

        $this->assertSame(Plugin::CRON_EVERY_FIVE_MINUTES, \wp_get_schedule(Plugin::RETRY_REQUESTS_CRON_JOB));
    }

    public function test_it_sets_the_default_values_for_order_options()
    {
        Plugin::onActivation();

        $this->assertSame(OrderOptionsTab::ORDER_ID_FIELD_DEFAULT_VALUE, \get_option(Option::OPTIONS_PREFIX . OrderOptionsTab::ORDER_ID_FIELD_NAME));
        $this->assertSame(OrderOptionsTab::TRACKING_LINK_FIELD_DEFAULT_VALUE, \get_option(Option::OPTIONS_PREFIX . OrderOptionsTab::TRACKING_LINK_FIELD_NAME));
        $this->assertSame(OrderOptionsTab::CARRIER_INFORMATION_FIELD_DEFAULT_VALUE, \get_option(Option::OPTIONS_PREFIX . OrderOptionsTab::CARRIER_INFORMATION_FIELD_NAME));
    }
}
