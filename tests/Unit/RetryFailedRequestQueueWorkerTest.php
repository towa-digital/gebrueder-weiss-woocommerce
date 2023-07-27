<?php

use Mockery\MockInterface;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Configuration;
use Towa\GebruederWeissSDK\Api\DefaultApi;
use Towa\GebruederWeissSDK\Model\InlineObject as CreateLogisticsOrderPayload;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequest;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\RetryFailedRequestsQueueWorker;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;
use Towa\GebruederWeissWooCommerce\OrderRepository;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use Towa\GebruederWeissWooCommerce\Support\WordPress;

uses(\Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration::class);

private const HTTP_STATUS_CONFLICT = 409;

private const STATUS_FAILED  = "wc-failed";

private const STATUS_PENDING = "on-hold";

private const TEST_ORDER_ID = 42;

beforeEach(function () {
    $this->gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $this->gebruederWeissApi->allows("logisticsOrderPost");
    $this->gebruederWeissApi->allows(["getConfig" => new Configuration()]);

    $this->logisticsOrderFactory = Mockery::mock(LogisticsOrderFactory::class);
    $this->logisticsOrderFactory->allows(["buildFromWooCommerceOrder" => new CreateLogisticsOrderPayload()]);

    /** @var MockInterface|WC_Order $order */
    $order = Mockery::mock(WC_Order::class);
    $order->allows([
        "set_status" => null,
        "save"       => null,
        "get_id"     => self::TEST_ORDER_ID
    ]);

    $this->orderRepository = Mockery::mock(OrderRepository::class);
    $this->orderRepository->allows(["findById" => $order]);

    $this->settingsRepository = Mockery::mock(SettingsRepository::class);
    $this->settingsRepository->allows([
        'getFulfillmentErrorState' => self::STATUS_FAILED,
        "getAccessToken"           => new OAuthToken("token", time() + 3600),
        "getPendingState"          => self::STATUS_PENDING
    ]);
});

test('it processes all requests that need a retry', function () {
    $failedRequest = new FailedRequest(2, 4);

    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository->allows("update");
    $failedRequestRepository
        ->shouldReceive("findOneToRetry")
        ->times(2)
        ->andReturn($failedRequest, null);

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $this->gebruederWeissApi,
        $this->orderRepository,
        $this->settingsRepository
    ))->start();
});

test('it retries the api call for each failed request', function () {
    $failedRequest1 = new FailedRequest(2, 4);
    $failedRequest2 = new FailedRequest(3, 5);

    /** @var MockInterface|DefaultApi $gebruederWeissApi */
    $gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $gebruederWeissApi->allows(["getConfig" => new Configuration()]);
    $gebruederWeissApi
        ->shouldReceive("logisticsOrderPost")
        ->times(2);

    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository->allows("update");
    $failedRequestRepository
        ->shouldReceive("findOneToRetry")
        ->andReturn($failedRequest1, $failedRequest2, null);

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $gebruederWeissApi,
        $this->orderRepository,
        $this->settingsRepository
    ))->start();
});

test('it marks requests as successful if they were successful', function () {
    /** @var FailedRequest|MockInterface $failedRequest */
    $failedRequest = Mockery::mock(FailedRequest::class);
    $failedRequest->allows([
        "getOrderId"        => 4,
        "setStatus"         => null,
        "getFailedAttempts" => 1
    ]);

    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository
        ->shouldReceive("update")
        ->once();
    $failedRequestRepository
        ->shouldReceive("findOneToRetry")
        ->andReturn($failedRequest, null);

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $this->gebruederWeissApi,
        $this->orderRepository,
        $this->settingsRepository
    ))->start();

    $failedRequest->shouldHaveReceived("setStatus", [FailedRequest::SUCCESS_STATUS]);
});

test('it increases the failed attempt counter on failures', function () {
    /** @var FailedRequest|MockInterface $failedRequest */
    $failedRequest = Mockery::mock(FailedRequest::class);
    $failedRequest->allows([
        "getOrderId"        => 4,
        "setStatus"         => null,
        "getFailedAttempts" => 1
    ]);
    $failedRequest
        ->shouldReceive("incrementFailedAttempts")
        ->once();

    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository
        ->shouldReceive("update")
        ->once();
    $failedRequestRepository
        ->shouldReceive("findOneToRetry")
        ->andReturn($failedRequest, null);

    /** @var MockInterface|DefaultApi $gebruederWeissApi */
    $gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $gebruederWeissApi->allows(["getConfig" => new Configuration()]);
    $gebruederWeissApi
        ->shouldReceive("logisticsOrderPost")
        ->andThrow(new ApiException("ups"));

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $gebruederWeissApi,
        $this->orderRepository,
        $this->settingsRepository
    ))->start();
});

/**
 * We need to isolate this test to able to alias mock the
 * WordPress class with our helper functions.
 *
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
test('it sends a mail if the request failed for the third time', function () {
    /** @var FailedRequest|MockInterface $failedRequest */
    $failedRequest = Mockery::mock(FailedRequest::class);
    $failedRequest->allows([
        "getOrderId"              => 4,
        "setStatus"               => null,
        "incrementFailedAttempts" => null
    ]);
    $failedRequest
        ->shouldReceive("getFailedAttempts")
        ->once()
        ->andReturn(3);

    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository->shouldReceive("update");
    $failedRequestRepository
        ->shouldReceive("findOneToRetry")
        ->andReturn($failedRequest, null);

    /** @var MockInterface|DefaultApi $gebruederWeissApi */
    $gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $gebruederWeissApi->allows(["getConfig" => new Configuration()]);
    $gebruederWeissApi
        ->shouldReceive("logisticsOrderPost")
        ->andThrow(new ApiException("ups"));

    /** @var MockInterface $wordpressMock */
    $wordpressMock = Mockery::mock("alias:" . WordPress::class);
    $wordpressMock
        ->shouldReceive("sendMailToAdmin")
        ->once();

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $gebruederWeissApi,
        $this->orderRepository,
        $this->settingsRepository
    ))->start();
});

/**
 * We need to isolate this test to able to alias mock the
 * WordPress class with our helper functions.
 *
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
test('it sets the order state to fulfillment error after the third failed try', function () {
    /** @var FailedRequest|MockInterface $failedRequest */
    $failedRequest = Mockery::mock(FailedRequest::class);
    $failedRequest->allows([
        "getOrderId"              => 4,
        "setStatus"               => null,
        "incrementFailedAttempts" => null
    ]);
    $failedRequest
        ->shouldReceive("getFailedAttempts")
        ->once()
        ->andReturn(3);

    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository->shouldReceive("update");
    $failedRequestRepository
        ->shouldReceive("findOneToRetry")
        ->andReturn($failedRequest, null);

    /** @var MockInterface|DefaultApi $gebruederWeissApi */
    $gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $gebruederWeissApi->allows(["getConfig" => new Configuration()]);
    $gebruederWeissApi
        ->shouldReceive("logisticsOrderPost")
        ->andThrow(new ApiException("ups"));

    /** @var MockInterface $wordpressMock */
    $wordpressMock = Mockery::mock("alias:" . WordPress::class);
    $wordpressMock->shouldReceive("sendMailToAdmin");

    /** @var MockInterface|WC_Order $order */
    $order = Mockery::mock(WC_Order::class);
    $order->allows(["get_id" => self::TEST_ORDER_ID]);
    $order
        ->shouldReceive("set_status")
        ->once()
        ->andReturn(null);
    $order
        ->shouldReceive("save")
        ->once();

    /** @var MockInterface|OrderRepository $orderRepository */
    $orderRepository = Mockery::mock(OrderRepository::class);
    $orderRepository->allows(["findById" => $order]);

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $gebruederWeissApi,
        $orderRepository,
        $this->settingsRepository
    ))->start();

    $order->shouldHaveReceived("set_status", [self::STATUS_FAILED]);
});

/**
 * We need to isolate this test to able to alias mock the
 * WordPress class with our helper functions.
 *
 * @runInSeparateProcess
 * @preserveGlobalState disabled
 */
test('it does not queue a request for retry again if there was a conflict error', function () {
    /** @var FailedRequest|MockInterface $failedRequest */
    $failedRequest = Mockery::mock(FailedRequest::class);
    $failedRequest->allows([
        "getOrderId"        => 4,
        "getFailedAttempts" => 1
    ]);
    $failedRequest
        ->shouldReceive("doNotRetry")
        ->times(1);

    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository->shouldReceive("update");
    $failedRequestRepository
        ->shouldReceive("findOneToRetry")
        ->andReturn($failedRequest, null);

    /** @var MockInterface|DefaultApi $gebruederWeissApi */
    $gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $gebruederWeissApi->allows(["getConfig" => new Configuration()]);
    $gebruederWeissApi
        ->shouldReceive("logisticsOrderPost")
        ->andThrow(new ApiException("Conflict", self::HTTP_STATUS_CONFLICT));

    /** @var MockInterface $wordpressMock */
    $wordpressMock = Mockery::mock("alias:" . WordPress::class);
    $wordpressMock
        ->shouldReceive("sendMailToAdmin")
        ->once();

    /** @var MockInterface|WC_Order $order */
    $order = Mockery::mock(WC_Order::class);
    $order->allows(["get_id" => self::TEST_ORDER_ID]);
    $order
        ->shouldReceive("set_status")
        ->once()
        ->andReturn(null);
    $order
        ->shouldReceive("save")
        ->once();

    /** @var MockInterface|OrderRepository $orderRepository */
    $orderRepository = Mockery::mock(OrderRepository::class);
    $orderRepository->allows(["findById" => $order]);

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $gebruederWeissApi,
        $orderRepository,
        $this->settingsRepository
    ))->start();

    $order->shouldHaveReceived("set_status", [self::STATUS_FAILED]);
});

test('it ensures that the requests are authenticated', function () {
    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository->allows(["findOneToRetry" => null]);

    /** @var MockInterface|Configuration $configuration */
    $configuration = Mockery::mock(Configuration::class);
    $configuration
        ->shouldReceive("setAccessToken")
        ->once()
        ->withArgs(["token"]);

    /** @var MockInterface|DefaultApi $gebruederWeissApi */
    $gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $gebruederWeissApi
        ->shouldReceive("getConfig")
        ->once()
        ->andReturn($configuration);

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $gebruederWeissApi,
        $this->orderRepository,
        $this->settingsRepository
    ))->start();
});

test('it marks the failed request as successful if the order cannot be found', function () {
    /** @var FailedRequest|MockInterface $failedRequest */
    $failedRequest = Mockery::mock(FailedRequest::class);
    $failedRequest->allows([
        "getOrderId"        => 4,
        "setStatus"         => null,
        "getFailedAttempts" => 1
    ]);

    /** @var FailedRequestRepository|MockInterface $failedRequestRepository */
    $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
    $failedRequestRepository
        ->shouldReceive("update")
        ->once();
    $failedRequestRepository
        ->shouldReceive("findOneToRetry")
        ->andReturn($failedRequest, null);

    /** @var MockInterface|OrderRepository $orderRepository */
    $orderRepository = Mockery::mock(OrderRepository::class);
    $orderRepository->allows(["findById" => null]);

    (new RetryFailedRequestsQueueWorker(
        $failedRequestRepository,
        $this->logisticsOrderFactory,
        $this->gebruederWeissApi,
        $orderRepository,
        $this->settingsRepository
    ))->start();

    $failedRequest->shouldHaveReceived("setStatus", [FailedRequest::SUCCESS_STATUS]);

    $failedRequestRepository->shouldHaveReceived("update", [$failedRequest]);
});
