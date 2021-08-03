<?php

namespace Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequest;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\FailedRequestRepository;
use Towa\GebruederWeissWooCommerce\FailedRequestQueue\RetryFailedRequestsQueueWorker;

class RetryFailedRequestsQueueWorkerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_it_processes_all_requests_that_need_a_retry()
    {
        $failedRequest = new FailedRequest(2, 4);

        /** @var FailedRequestRepository|MockInterface */
        $failedRequestRepository = Mockery::mock(FailedRequestRepository::class);
        $failedRequestRepository->shouldReceive("findOneToRetry")->times(2)->andReturn($failedRequest, null);

        $worker = new RetryFailedRequestsQueueWorker($failedRequestRepository);
        $worker->start();
    }
}
