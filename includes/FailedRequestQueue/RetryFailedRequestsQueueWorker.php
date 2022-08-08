<?php
/**
 * Retry Failed Request Queue Worker
 *
 * @package FailedRequestQueue
 */

namespace Towa\GebruederWeissWooCommerce\FailedRequestQueue;

defined('ABSPATH') || exit;

use Towa\GebruederWeissSDK\Api\DefaultApi;
use Towa\GebruederWeissWooCommerce\CreateLogisticsOrderCommand;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderConflictException;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderFailedException;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\OrderRepository;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
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
     * @var DefaultApi
     */
    private $gebruederWeissApi = null;

    /**
     * Order Repository
     *
     * @var OrderRepository
     */
    private $orderRepository = null;

    /**
     * Settings Repository
     *
     * @var SettingsRepository
     */
    private $settingsRepository = null;

    /**
     * Constructor.
     *
     * @param FailedRequestRepository $failedRequestRepository Failed Requests Repository.
     * @param LogisticsOrderFactory   $logisticsOrderFactory Logistics Order Factory.
     * @param DefaultApi              $gebruederWeissApi Gebrueder Weiss Write API.
     * @param OrderRepository         $orderRepository WooCommerce Order Repository.
     * @param SettingsRepository      $settingsRepository Settings Repository.
     */
    public function __construct(
        FailedRequestRepository $failedRequestRepository,
        LogisticsOrderFactory $logisticsOrderFactory,
        DefaultApi $gebruederWeissApi,
        OrderRepository $orderRepository,
        SettingsRepository $settingsRepository
    ) {
        $this->failedRequestRepository = $failedRequestRepository;
        $this->gebruederWeissApi = $gebruederWeissApi;
        $this->logisticsOrderFactory = $logisticsOrderFactory;
        $this->orderRepository = $orderRepository;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Process all failed requests that should be retried.
     *
     * @return void
     */
    public function start(): void
    {
        $this->gebruederWeissApi->getConfig()->setAccessToken($this->settingsRepository->getAccessToken()->getToken());

        while (true) {
            $failedRequest = $this->failedRequestRepository->findOneToRetry();

            if (is_null($failedRequest)) {
                break;
            }

            $order = $this->orderRepository->findById($failedRequest->getOrderId());

            if (is_null($order)) {
                $failedRequest->setStatus(FailedRequest::SUCCESS_STATUS);
                $this->failedRequestRepository->update($failedRequest);
                return;
            }

            try {
                (new CreateLogisticsOrderCommand(
                    $order,
                    $this->logisticsOrderFactory,
                    $this->gebruederWeissApi
                ))->execute();

                $failedRequest->setStatus(FailedRequest::SUCCESS_STATUS);
            } catch (CreateLogisticsOrderConflictException $e) {
                $failedRequest->doNotRetry();

                $order->set_status($this->settingsRepository->getFulfillmentErrorState());
                $order->save();

                $orderId = $order->get_id();
                Wordpress::sendMailToAdmin("Gebrueder Weiss Fulfillment Failed for Order #$orderId", "Creating the Gebrueder Weiss logistics order for the WooCommerce order #$orderId failed due to a conflict with the following error:\n\n{$e->getMessage()}");
            } catch (CreateLogisticsOrderFailedException $e) {
                $failedRequest->incrementFailedAttempts();

                if ($failedRequest->getFailedAttempts() === FailedRequest::MAX_ATTEMPTS) {
                    $order->set_status($this->settingsRepository->getFulfillmentErrorState());
                    $order->save();

                    $orderId = $order->get_id();
                    Wordpress::sendMailToAdmin("Gebrueder Weiss Fulfillment Failed for Order #$orderId", "Creating the Gebrueder Weiss logistics order for the WooCommerce order #$orderId failed with the following error:\n\n{$e->getMessage()}");
                }
            }

            $this->failedRequestRepository->update($failedRequest);
        }
    }
}
