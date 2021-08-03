<?php
/**
 * Retry Failed Request Queue Worker
 *
 * @package FailedRequestQueue
 */

namespace Towa\GebruederWeissWooCommerce\FailedRequestQueue;

defined('ABSPATH') || exit;


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
     * Constructor.
     *
     * @param FailedRequestRepository $repository FailedRequestsRepository.
     */
    public function __construct(FailedRequestRepository $repository)
    {
        $this->failedRequestRepository = $repository;
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
        }
    }
}
