<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\Options\Option;
use Towa\GebruederWeissWooCommerce\Options\OrderOptionsTab;
use Towa\GebruederWeissWooCommerce\Plugin;


test('it creates the request queue table', function () {
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
    expect($wpdb->last_error)->toBeEmpty();
});

test('it adds the retry requests cron job', function () {
    Plugin::onActivation();

    expect(\wp_get_schedule(Plugin::RETRY_REQUESTS_CRON_JOB))->toBe(Plugin::CRON_EVERY_FIVE_MINUTES);
});

test('it sets the default values for order options', function () {
    Plugin::onActivation();

    expect(\get_option(Option::OPTIONS_PREFIX . OrderOptionsTab::ORDER_ID_FIELD_NAME))->toBe(OrderOptionsTab::ORDER_ID_FIELD_DEFAULT_VALUE);
    expect(\get_option(Option::OPTIONS_PREFIX . OrderOptionsTab::TRACKING_LINK_FIELD_NAME))->toBe(OrderOptionsTab::TRACKING_LINK_FIELD_DEFAULT_VALUE);
    expect(\get_option(Option::OPTIONS_PREFIX . OrderOptionsTab::CARRIER_INFORMATION_FIELD_NAME))->toBe(OrderOptionsTab::CARRIER_INFORMATION_FIELD_DEFAULT_VALUE);
});
