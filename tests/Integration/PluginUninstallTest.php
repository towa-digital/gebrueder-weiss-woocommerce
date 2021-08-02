<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\Plugin;


class PluginUninstallTest extends \WP_UnitTestCase
{
    public function test_it_removes_the_request_retry_queue_table()
    {
        global $wpdb;

        // Prevent errors from being dumped into the console. They will be shown when the assertion fails.
        $wpdb->suppress_errors();

        Plugin::onActivation();
        Plugin::onUninstall();

        /**
         * The WordPress tables forces all tables created during a test to be a temporary table.
         * Hence, the table will not be listed in the output of "show table" or
         * similar statements. As an alternative we try to execute a query
         * against the table and check if there was an error.
         */
        $wpdb->get_var("select count(*) from {$wpdb->prefix}gbw_request_retry_queue");
        $this->assertNotEmpty($wpdb->last_error);
    }

    public function test_it_removes_the_plugin_settings()
    {
        Plugin::getInstance()->initOptionPage();

        update_option("gbw_customer_id", "customer-id");
        update_option("gbw_client_id", "client-id");
        update_option("gbw_client_secret", "client-secret");
        update_option("gbw_fulfillmentState", "wc-fulfillment-state");
        update_option("gbw_fulfilledState", "wc-fulfilled-state");
        update_option("gbw_fulfillmentErrorState", "wc-fulfillment-error-state");

        Plugin::onUninstall();

        $this->assertNull(get_option("gbw_customer_id", null));
        $this->assertNull(get_option("gbw_client_id", null));
        $this->assertNull(get_option("gbw_client_secret", null));
        $this->assertNull(get_option("gbw_fulfillmentState", null));
        $this->assertNull(get_option("gbw_fulfilledState", null));
        $this->assertNull(get_option("gbw_fulfillmentErrorState", null));
    }
}
