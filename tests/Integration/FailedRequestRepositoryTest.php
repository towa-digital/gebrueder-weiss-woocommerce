<?php

namespace Tests\Integration;

use DateTime;
use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequest;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;

class FailedRequestRepositoryTest extends \WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Plugin::onActivation();
    }

    public function test_it_can_create_a_failed_request()
    {
        global $wpdb;

        $repository = new FailedRequestRepository();

        $request = $repository->create(12, FailedRequest::FAILED_STATUS, 2);

        $numberOfRows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}gbw_request_retry_queue WHERE order_id = {$request->getOrderId()} AND status = \"{$request->getStatus()}\" AND failed_attempts = {$request->getFailedAttempts()}");
        $this->assertSame(1, intval($numberOfRows));
    }

    public function test_it_can_update_a_failed_request()
    {
        $repository = new FailedRequestRepository();
        $request = $repository->create(12, FailedRequest::FAILED_STATUS, 2);

        $request->setStatus(FailedRequest::SUCCESS_STATUS);
        $request->incrementFailedAttempts();
        $repository->update($request);

        $this->assertSame(1, $this->getNumberOfFailedRequestsInDB());
    }

    public function test_it_deletes_requests_if_they_were_successful()
    {
        Plugin::onActivation();

        $repository = new FailedRequestRepository();
        $repository->create(12, FailedRequest::SUCCESS_STATUS, 2);

        $repository->deleteWhereStale();

        $this->assertSame(0, $this->getNumberOfFailedRequestsInDB());
    }

    public function test_it_deletes_requests_if_they_have_been_tried_three_times()
    {
        Plugin::onActivation();

        $repository = new FailedRequestRepository();
        $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS);

        $repository->deleteWhereStale();

        $this->assertSame(0, $this->getNumberOfFailedRequestsInDB());
    }

    public function test_it_can_find_a_request_to_retry()
    {
        $repository = new FailedRequestRepository();
        $oneHourAgo = DateTime::createFromFormat("U", time() - 3600);
        $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS - 1, $oneHourAgo);

        $request = $repository->findOneToRetry();

        $this->assertSame(12, $request->getOrderId());
        $this->assertSame(FailedRequest::FAILED_STATUS, $request->getStatus());
        $this->assertSame(2, $request->getFailedAttempts());
    }

    public function test_it_does_not_return_requests_that_have_been_retried_less_then_five_minutes_ago()
    {
        $repository = new FailedRequestRepository();
        $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS - 1, new DateTime());

        $request = $repository->findOneToRetry();

        $this->assertNull($request);
    }

    public function test_it_returns_null_when_no_request_to_retry_is_available()
    {
        $repository = new FailedRequestRepository();
        $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS);

        $request = $repository->findOneToRetry();

        $this->assertNull($request);
    }

    private function getNumberOfFailedRequestsInDB(): int
    {
        global $wpdb;

        $numberOfRows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}gbw_request_retry_queue");

        return intval($numberOfRows);
    }
}
