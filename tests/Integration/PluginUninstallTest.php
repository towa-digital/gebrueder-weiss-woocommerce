<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\Plugin;


test('it removes the request retry queue table', function () {
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
    expect($wpdb->last_error)->not->toBeEmpty();
});

test('it removes the plugin settings', function () {
    Plugin::getInstance()->initOptionPage();

    update_option("gbw_customer_id", "customer-id");
    update_option("gbw_client_id", "client-id");
    update_option("gbw_client_secret", "client-secret");
    update_option("gbw_fulfillmentState", "wc-fulfillment-state");
    update_option("gbw_fulfilledState", "wc-fulfilled-state");
    update_option("gbw_fulfillmentErrorState", "wc-fulfillment-error-state");

    Plugin::onUninstall();

    expect(get_option("gbw_customer_id", null))->toBeNull();
    expect(get_option("gbw_client_id", null))->toBeNull();
    expect(get_option("gbw_client_secret", null))->toBeNull();
    expect(get_option("gbw_fulfillmentState", null))->toBeNull();
    expect(get_option("gbw_fulfilledState", null))->toBeNull();
    expect(get_option("gbw_fulfillmentErrorState", null))->toBeNull();
});
