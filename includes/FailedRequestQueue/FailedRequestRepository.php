<?php
/**
 * Failed Request Repository
 *
 * Holds FailedRequests.
 *
 * @package FailedRequestQueue
 */

namespace Towa\GebruederWeissWooCommerce\FailedRequestQueue;

defined('ABSPATH') || exit;

/**
 * FailedRequest Class
 */
class FailedRequestRepository
{
    public function findOneToRetry(): ?FailedRequest
    {
        global $wpdb;

        $statement = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}gbw_request_retry_queue WHERE status = \"%s\" AND failed_attempts < %d LIMIT 1", [FailedRequest::FAILED_STATUS, FailedRequest::MAX_ATTEMPTS]);
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
     * @param integer $orderId The related WooCommerce order id.
     * @param string  $status Status of the request, defaults to failed.
     * @param integer $failedAttempts The number of failed attempts, defaults to 1.
     * @return FailedRequest
     */
    public function create(int $orderId, string $status = FailedRequest::FAILED_STATUS, int $failedAttempts = 1): FailedRequest
    {
        global $wpdb;

        $statement = $wpdb->prepare("INSERT INTO {$wpdb->prefix}gbw_request_retry_queue (order_id, status, failed_attempts) VALUES (%d, \"%s\", %d)", [$orderId, $status, $failedAttempts]);
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
     * This methods mirrors the values of the failed request object to the corresponding database row.
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
        ];

        $where = [ "id" => $failedRequest->getId() ];

        $format = [ "%d", "%d", "%s"];

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
