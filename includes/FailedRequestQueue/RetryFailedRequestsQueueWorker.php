<?php
/**
 * Retry Failed Request Queue Worker
 *
 * @package FailedRequestQueue
 */

namespace Towa\GebruederWeissWooCommerce\FailedRequestQueue;

defined('ABSPATH') || exit;

use Towa\GebruederWeissSDK\Api\WriteApi;
use Towa\GebruederWeissWooCommerce\CreateLogisticsOrderCommand;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderFailedException;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\OrderRepository;
use Towa\GebruederWeissWooCommerce\Support\WordPress;

/**
 * Retry Failed Request Queue Worker
 */
class RetryFailedRequestsQueueWorker
{
    /**
     * Repository to access failed requests
     *
     * @var FailedRequestRepository $failedRequestRepository the repo.
     */
    private $failedRequestRepository = null;

    /**
     * Logistics Order Factory
     *
     * @var LogisticsOrderFactory
     */
    private $logisticsOrderFactory = null;

    /**
     * Gebrueder Weiss Write API
     *
     * @var WriteApi
     */
    private $writeApi = null;

    /**
     * Order Repository
     *
     * @var OrderRepository
     */
    private $orderRepository = null;

    /**
     * Constructor.
     *
     * @param FailedRequestRepository $failedRequestRepository Failed Requests Repository.
     * @param LogisticsOrderFactory   $logisticsOrderFactory Logistics Order Factory.
     * @param WriteApi                $writeApi Gebrueder Weiss Write API.
     * @param OrderRepository         $orderRepository WooCommerce Order Repository.
     */
    public function __construct(
        FailedRequestRepository $failedRequestRepository,
        LogisticsOrderFactory $logisticsOrderFactory,
        WriteApi $writeApi,
        OrderRepository $orderRepository
    ) {
        $this->failedRequestRepository = $failedRequestRepository;
        $this->writeApi = $writeApi;
        $this->logisticsOrderFactory = $logisticsOrderFactory;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Process all failed requests that should be retried.
     *
     * @return void
     */
    public function start(): void
    {
        while (true) {
            $failedRequest = $this->failedRequestRepository->findOneToRetry();

            if (is_null($failedRequest)) {
                break;
            }

            $order = $this->orderRepository->findById($failedRequest->getOrderId());

            try {
                (new CreateLogisticsOrderCommand(
                    $order,
                    $this->logisticsOrderFactory,
                    $this->writeApi
                ))->execute();

                $failedRequest->setStatus(FailedRequest::SUCCESS_STATUS);
            } catch (CreateLogisticsOrderFailedException $e) {
                $failedRequest->incrementFailedAttempts();
            }

            if ($failedRequest->getFailedAttempts() === FailedRequest::MAX_ATTEMPTS) {
                Wordpress::sendErrorNotificationToAdmin("error", "placing logistics order failed");
            }

            $this->failedRequestRepository->update($failedRequest);
        }
    }
}
