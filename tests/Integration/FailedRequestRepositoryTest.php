<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequest;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;

class FailedRequestRepositoryTest extends \WP_UnitTestCase
{
    public function test_it_can_create_a_failed_request()
    {
        global $wpdb;
        Plugin::onActivation();

        $repository = new FailedRequestRepository();

        $request = $repository->create(12, FailedRequest::FAILED_STATUS, 2);

        $numberOfRows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}gbw_request_retry_queue WHERE order_id = {$request->getOrderId()} AND status = \"{$request->getStatus()}\" AND failed_attempts = {$request->getFailedAttempts()}");
        $this->assertSame(1, intval($numberOfRows));
    }

    public function test_it_can_update_a_failed_request()
    {
        global $wpdb;
        Plugin::onActivation();

        $repository = new FailedRequestRepository();
        $request = $repository->create(12, FailedRequest::FAILED_STATUS, 2);

        $request->setStatus(FailedRequest::SUCCESS_STATUS);
        $request->incrementFailedAttempts();
        $repository->update($request);

        $numberOfRows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}gbw_request_retry_queue WHERE status = \"{$request->getStatus()}\" AND failed_attempts = {$request->getFailedAttempts()}");
        $this->assertSame(1, intval($numberOfRows));
    }

    public function test_it_deletes_requests_if_they_were_successful()
    {
        global $wpdb;
        Plugin::onActivation();

        $repository = new FailedRequestRepository();
        $repository->create(12, FailedRequest::SUCCESS_STATUS, 2);

        $repository->deleteWhereStale();

        $numberOfRows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}gbw_request_retry_queue");
        $this->assertSame(0, intval($numberOfRows));
    }

    public function test_it_can_find_a_request_to_retry()
    {
        Plugin::onActivation();

        $repository = new FailedRequestRepository();
        $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS - 1);

        $request = $repository->findOneToRetry();

        $this->assertSame(12, $request->getOrderId());
        $this->assertSame(FailedRequest::FAILED_STATUS, $request->getStatus());
        $this->assertSame(2, $request->getFailedAttempts());
    }

    public function test_it_returns_null_when_no_request_to_retry_is_available()
    {
        $repository = new FailedRequestRepository();
        $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS);

        $request = $repository->findOneToRetry();

        $this->assertNull($request);
    }
}
