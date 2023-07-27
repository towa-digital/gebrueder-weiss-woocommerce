<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequest;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;


beforeEach(function () {
    Plugin::onActivation();
});

afterEach(function () {
    Plugin::onUninstall();
});

test('it can create a failed request', function () {
    global $wpdb;

    $repository = new FailedRequestRepository();

    $request = $repository->create(12, FailedRequest::FAILED_STATUS, 2);

    $numberOfRows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->base_prefix}gbw_request_retry_queue WHERE order_id = {$request->getOrderId()} AND status = \"{$request->getStatus()}\" AND failed_attempts = {$request->getFailedAttempts()}");
    expect(intval($numberOfRows))->toBe(1);
});

test('it can update a failed request', function () {
    $repository = new FailedRequestRepository();
    $request = $repository->create(12, FailedRequest::FAILED_STATUS, 2);

    $request->setStatus(FailedRequest::SUCCESS_STATUS);
    $request->incrementFailedAttempts();
    $repository->update($request);

    $row = getFirstFailedRequest();
    expect($row->status)->toBe(FailedRequest::SUCCESS_STATUS);
    expect($row->failed_attempts)->toBe("3");
});

test('it deletes requests if they were successful', function () {
    Plugin::onActivation();

    $repository = new FailedRequestRepository();
    $repository->create(12, FailedRequest::SUCCESS_STATUS, 2);

    $repository->deleteWhereStale();

    expect(getNumberOfFailedRequestsInDB())->toBe(0);
});

test('it deletes requests if they have been tried three times', function () {
    Plugin::onActivation();

    $repository = new FailedRequestRepository();
    $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS);

    $repository->deleteWhereStale();

    expect(getNumberOfFailedRequestsInDB())->toBe(0);
});

test('it can find a request to retry', function () {
    $repository = new FailedRequestRepository();
    $oneHourAgo = DateTime::createFromFormat("U", time() - 3600);
    $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS - 1, $oneHourAgo);

    $request = $repository->findOneToRetry();

    expect($request->getOrderId())->toBe(12);
    expect($request->getStatus())->toBe(FailedRequest::FAILED_STATUS);
    expect($request->getFailedAttempts())->toBe(2);
});

test('it does not return requests that have been retried less then five minutes ago', function () {
    $repository = new FailedRequestRepository();
    $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS - 1);

    $request = $repository->findOneToRetry();

    expect($request)->toBeNull();
});

test('it returns null when no request to retry is available', function () {
    $repository = new FailedRequestRepository();
    $repository->create(12, FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS);

    $request = $repository->findOneToRetry();

    expect($request)->toBeNull();
});

function getNumberOfFailedRequestsInDB() : int
{
    global $wpdb;

    $numberOfRows = $wpdb->get_var("SELECT count(*) FROM {$wpdb->base_prefix}gbw_request_retry_queue");

    return intval($numberOfRows);
}

function getFirstFailedRequest() : object
{
    global $wpdb;

    return $wpdb->get_row("SELECT * FROM {$wpdb->base_prefix}gbw_request_retry_queue LIMIT 1");
}
