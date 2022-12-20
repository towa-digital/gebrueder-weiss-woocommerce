<?php

namespace Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
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
use WC_Order;

class RetryFailedRequestsQueueWorkerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const STATUS_FAILED  = "wc-failed";
    private const STATUS_PENDING = "on-hold";

    /** @var MockInterface|DefaultApi */
    private $gebruederWeissApi;

    /** @var MockInterface|LogisticsOrderFactory */
    private $logisticsOrderFactory;

    /** @var MockInterface|OrderRepository */
    private $orderRepository;

    /** @var MockInterface|SettingsRepository */
    private $settingsRepository;

    public function setUp(): void
    {
        parent::setUp();

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
            "get_id"     => 42
        ]);

        $this->orderRepository = Mockery::mock(OrderRepository::class);
        $this->orderRepository->allows(["findById" => $order]);

        $this->settingsRepository = Mockery::mock(SettingsRepository::class);
        $this->settingsRepository->allows([
            'getFulfillmentErrorState' => self::STATUS_FAILED,
            "getAccessToken"           => new OAuthToken("token", time() + 3600),
            "getPendingState"          => self::STATUS_PENDING
        ]);
    }

    public function test_it_processes_all_requests_that_need_a_retry()
    {
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
    }

    public function test_it_retries_the_api_call_for_each_failed_request()
    {
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
    }

    public function test_it_marks_requests_as_successful_if_they_were_successful()
    {
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
    }

    public function test_it_increases_the_failed_attempt_counter_on_failures()
    {
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
    }

    /**
     * We need to isolate this test to able to alias mock the
     * WordPress class with our helper functions.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_it_sends_a_mail_if_the_request_failed_for_the_third_time()
    {
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
    }

    /**
     * We need to isolate this test to able to alias mock the
     * WordPress class with our helper functions.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_it_sets_the_order_state_to_fulfillment_error_after_the_third_failed_try()
    {
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
        $order->allows(["get_id" => 42]);
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
    }

    /**
     * We need to isolate this test to able to alias mock the
     * WordPress class with our helper functions.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_it_does_not_queue_a_request_for_retry_again_if_there_was_a_conflict_error()
    {
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
            ->andThrow(new ApiException("Conflict", 409));

        /** @var MockInterface $wordpressMock */
        $wordpressMock = Mockery::mock("alias:" . WordPress::class);
        $wordpressMock
            ->shouldReceive("sendMailToAdmin")
            ->once();

        /** @var MockInterface|WC_Order $order */
        $order = Mockery::mock(WC_Order::class);
        $order->allows(["get_id" => 42]);
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
    }

    public function test_it_ensures_that_the_requests_are_authenticated()
    {
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
    }

    public function test_it_marks_the_failed_request_as_successful_if_the_order_cannot_be_found()
    {
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
    }
}
