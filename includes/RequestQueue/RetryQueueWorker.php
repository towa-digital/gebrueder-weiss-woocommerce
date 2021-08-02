<?php
/**
 * Retry Queue Worker
 *
 * Manages the items of FailedRequestRepository
 *
 * @package RequestQueue
 */

namespace Towa\GebruederWeissWooCommerce\RequestQueue;

defined('ABSPATH') || exit;


/**
 * FailedRequest Class
 */
class RetryQueueWorker
{
    /**
     * Holds all FailedRequest objects
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
     * Adds Request to the Repository.
     *
     * @param FailedRequest $request the request to add.
     * @return void
     */
    public function save(FailedRequest $request)
    {
        // implement save functionality.
    }

    /**
     * Removes requests from the repository
     *
     * @return void
     */
    public function deleteWhereStale()
    {
        // implement deleteWhereStale functionality.
    }

    /**
     * Increases counter on request.
     *
     * @param FailedRequest $request the request where the counter has to be incremented.
     * @return void
     */
    public function increaseCounter(FailedRequest $request)
    {
        // implement increaseCounter functionality.
    }
}
