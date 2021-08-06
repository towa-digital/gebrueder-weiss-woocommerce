<?php
/**
 * Failed Request Repository
 *
 * @package FailedRequestQueue
 */

namespace Towa\GebruederWeissWooCommerce\FailedRequestQueue;

defined('ABSPATH') || exit;

use DateTime;

/**
 * FailedRequest Class
 */
class FailedRequestRepository
{
    /**
     * Returns the next request to retry
     *
     * @return FailedRequest|null
     */
    public function findOneToRetry(): ?FailedRequest
    {
        global $wpdb;

        $statement = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gbw_request_retry_queue
             WHERE
                status = \"%s\" AND
                failed_attempts < %d AND
                last_attempt_date < CURRENT_TIME - INTERVAL 5 MINUTE
             LIMIT 1",
            [FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS]
        );

        $row = $wpdb->get_row($statement);

        if (is_null($row)) {
            return null;
        }

        return new FailedRequest(
            $row->id,
            $row->order_id,
            $row->status,
            $row->failed_attempts
        );
    }

    /**
     * Creates a failed request based on the passed data
     *
     * @param integer  $orderId The related WooCommerce order id.
     * @param string   $status Status of the request, defaults to failed.
     * @param integer  $failedAttempts The number of failed attempts, defaults to 1.
     * @param DateTime $lastAttemptedDate The date when the request was last attempted.
     * @return FailedRequest
     */
    public function create(int $orderId, string $status = FailedRequest::FAILED_STATUS, int $failedAttempts = 1, DateTime $lastAttemptedDate = null): FailedRequest
    {
        global $wpdb;

        $lastAttemptedDate = $lastAttemptedDate ?? new DateTime();

        $statement = $wpdb->prepare("INSERT INTO {$wpdb->prefix}gbw_request_retry_queue (order_id, status, failed_attempts, last_attempt_date) VALUES (%d, \"%s\", %d, \"%s\")", [$orderId, $status, $failedAttempts, $lastAttemptedDate->format("Y-m-d H:i:s")]);
        $wpdb->query($statement);

        $failedRequest = new FailedRequest(
            $wpdb->insert_id,
            $orderId,
            $status,
            $failedAttempts
        );

        return $failedRequest;
    }

    /**
     * Updates a failed request.
     *
     * This method stores the values of the failed request object in the corresponding database row.
     *
     * @param FailedRequest $failedRequest The failed request to be updated in the database.
     * @return void
     */
    public function update(FailedRequest $failedRequest): void
    {
        global $wpdb;

        $data = [
            "order_id" => $failedRequest->getOrderId(),
            "failed_attempts" => $failedRequest->getFailedAttempts(),
            "status" => $failedRequest->getStatus(),
            "last_attempt_date" => $failedRequest->getLastAttemptDate()->format("Y-m-d H:i:s"),
        ];

        $where = [ "id" => $failedRequest->getId() ];

        $format = [ "%d", "%d", "%s", "%s"];

        $whereFormat = [ "%d" ];

        $wpdb->update(
            "{$wpdb->prefix}gbw_request_retry_queue",
            $data,
            $where,
            $format,
            $whereFormat,
        );
    }

    /**
     * Deletes stale requests.
     *
     * A request is considered stale if the state is successful or if it has been tried more than three times.
     *
     * @return void
     */
    public function deleteWhereStale()
    {
        global $wpdb;

        $statement = $wpdb->prepare("DELETE FROM {$wpdb->prefix}gbw_request_retry_queue WHERE status = \"%s\" OR failed_attempts >= %d", [FailedRequest::SUCCESS_STATUS, FailedRequest::MAX_ATTEMPTS]);
        $wpdb->get_results($statement);
    }
}
