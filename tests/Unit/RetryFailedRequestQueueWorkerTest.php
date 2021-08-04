<?php

namespace Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Towa\GebruederWeissSDK\Api\WriteApi;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Model\LogisticsOrder;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequest;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\RetryFailedRequestsQueueWorker;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\OrderRepository;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use Towa\GebruederWeissWooCommerce\Support\WordPress;

class RetryFailedRequestsQueueWorkerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MockInterface|WriteApi */
    private $writeApi;

    /** @var MockInterface|LogisticsOrderFactory */
    private $logisticsOrderFactory;

    /** @var MockInterface|OrderRepository */
    private $orderRepository;

    /** @var MockInterface|SettingsRepository */
    private $settingsRepository;

    public function setUp(): void
    {
        parent::setUp();

        /** @var MockInterface|WriteApi */
        $this->writeApi = Mockery::mock(WriteApi::class);
        $this->writeApi->allows("logisticsOrderPost");

        /** @var MockInterface|LogisticsOrderFactory */
        $this->logisticsOrderFactory = Mockery::mock(LogisticsOrderFactory::class);
        $this->logisticsOrderFactory->allows([
            "buildFromWooCommerceOrder" => new LogisticsOrder(),
        ]);

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows([
            "set_status" => null,
            "save" => null,
            "get_id" => 42
        ]);

        /** @var MockInterface|OrderRepository */
        $this->orderRepository = Mockery::mock(OrderRepository::class);
        $this->orderRepository->allows([
            "findById" => $order
        ]);

        /** @var SettingsRepository|MockInterface */
        $this->settingsRepository = Mockery::mock(SettingsRepository::class);
        $this->settingsRepository->allows(['getFulfillmentErrorState' => 'wc-failed']);
    }

    public function test_it_processes_all_requests_that_need_a_retry()
    {
        $failedRequest = new FailedRequest(2, 4);

        /** @var FailedRequestRepository|MockInterface */
        $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $failedRequestRepository->allows("update");
        $failedRequestRepository->shouldReceive("findOneToRetry")->times(2)->andReturn($failedRequest, null);

        $worker = new RetryFailedRequestsQueueWorker($failedRequestRepository, $this->logisticsOrderFactory, $this->writeApi, $this->orderRepository, $this->settingsRepository);
        $worker->start();
    }

    public function test_it_retries_the_api_call_for_each_failed_request()
    {
        $failedRequest1 = new FailedRequest(2, 4);
        $failedRequest2 = new FailedRequest(3, 5);

        /** @var MockInterface|WriteApi */
        $writeApi = Mockery::mock(WriteApi::class);
        $writeApi->shouldReceive("logisticsOrderPost")->times(2);

        /** @var FailedRequestRepository|MockInterface */
        $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $failedRequestRepository->allows("update");
        $failedRequestRepository->shouldReceive("findOneToRetry")->andReturn($failedRequest1, $failedRequest2, null);

        $worker = new RetryFailedRequestsQueueWorker($failedRequestRepository, $this->logisticsOrderFactory, $writeApi, $this->orderRepository, $this->settingsRepository);
        $worker->start();
    }

    public function test_it_marks_requests_as_successful_if_they_were_successful()
    {
        /** @var FailedRequest|MockInterface */
        $failedRequest = Mockery::mock(FailedRequest::class);
        $failedRequest->allows([ "getOrderId" => 4, "setStatus" => null, "getFailedAttempts" => 1 ]);

        /** @var FailedRequestRepository|MockInterface */
        $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $failedRequestRepository->shouldReceive("update")->once();
        $failedRequestRepository->shouldReceive("findOneToRetry")->andReturn($failedRequest, null);

        $worker = new RetryFailedRequestsQueueWorker($failedRequestRepository, $this->logisticsOrderFactory, $this->writeApi, $this->orderRepository, $this->settingsRepository);
        $worker->start();

        $failedRequest->shouldHaveReceived("setStatus", [FailedRequest::SUCCESS_STATUS]);
    }

    public function test_it_increases_the_failed_attempt_counter_on_failures()
    {
        /** @var FailedRequest|MockInterface */
        $failedRequest = Mockery::mock(FailedRequest::class);
        $failedRequest->allows([ "getOrderId" => 4, "setStatus" => null, "getFailedAttempts" => 1 ]);
        $failedRequest->shouldReceive("incrementFailedAttempts")->once();

        /** @var FailedRequestRepository|MockInterface */
        $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $failedRequestRepository->shouldReceive("update")->once();
        $failedRequestRepository->shouldReceive("findOneToRetry")->andReturn($failedRequest, null);

        /** @var MockInterface|WriteApi */
        $writeApi = Mockery::mock(WriteApi::class);
        $writeApi->shouldReceive("logisticsOrderPost")->andThrow(new ApiException("ups"));

        $worker = new RetryFailedRequestsQueueWorker($failedRequestRepository, $this->logisticsOrderFactory, $writeApi, $this->orderRepository, $this->settingsRepository);
        $worker->start();
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
        /** @var FailedRequest|MockInterface */
        $failedRequest = Mockery::mock(FailedRequest::class);
        $failedRequest->allows([ "getOrderId" => 4, "setStatus" => null, "incrementFailedAttempts" => null ]);
        $failedRequest->shouldReceive("getFailedAttempts")->once()->andReturn(3);

        /** @var FailedRequestRepository|MockInterface */
        $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $failedRequestRepository->shouldReceive("update")->once();
        $failedRequestRepository->shouldReceive("findOneToRetry")->andReturn($failedRequest, null);

        /** @var MockInterface|WriteApi */
        $writeApi = Mockery::mock(WriteApi::class);
        $writeApi->shouldReceive("logisticsOrderPost")->andThrow(new ApiException("ups"));

        /** @var MockInterface */
        $wordpressMock = Mockery::mock("alias:" . WordPress::class);
        $wordpressMock->shouldReceive("sendMailToAdmin")->once();

        $worker = new RetryFailedRequestsQueueWorker($failedRequestRepository, $this->logisticsOrderFactory, $writeApi, $this->orderRepository, $this->settingsRepository);
        $worker->start();
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
        /** @var FailedRequest|MockInterface */
        $failedRequest = Mockery::mock(FailedRequest::class);
        $failedRequest->allows([ "getOrderId" => 4, "setStatus" => null, "incrementFailedAttempts" => null ]);
        $failedRequest->shouldReceive("getFailedAttempts")->once()->andReturn(3);

        /** @var FailedRequestRepository|MockInterface */
        $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $failedRequestRepository->shouldReceive("update")->once();
        $failedRequestRepository->shouldReceive("findOneToRetry")->andReturn($failedRequest, null);

        /** @var MockInterface|WriteApi */
        $writeApi = Mockery::mock(WriteApi::class);
        $writeApi->shouldReceive("logisticsOrderPost")->andThrow(new ApiException("ups"));

        /** @var MockInterface */
        $wordpressMock = Mockery::mock("alias:" . WordPress::class);
        $wordpressMock->shouldReceive("sendMailToAdmin");

        /** @var MockInterface|\WC_Order */
        $order = Mockery::mock("WC_Order");
        $order->allows([
            "save" => null,
            "get_id" => 42
        ]);
        $order->shouldReceive("set_status")->once()->andReturn(null);

        /** @var MockInterface|OrderRepository */
        $orderRepository = Mockery::mock(OrderRepository::class);
        $orderRepository->allows([
            "findById" => $order
        ]);

        $worker = new RetryFailedRequestsQueueWorker($failedRequestRepository, $this->logisticsOrderFactory, $writeApi, $orderRepository, $this->settingsRepository);
        $worker->start();

        // wc-failed is the value returned by the settings repository
        $order->shouldHaveReceived("set_status", ["wc-failed"]);
    }

    public function test_it_ensures_that_the_requests_are_authenticated()
    {
        $this->markTestIncomplete();
    }
}
