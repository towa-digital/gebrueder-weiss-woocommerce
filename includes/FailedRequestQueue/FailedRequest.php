<?php
/**
 * Failed Request
 *
 * Holds all information on a failed request against the Gebrueder Weiss API.
 *
 * @package FailedRequestQueue
 */

namespace Towa\GebruederWeissWooCommerce\FailedRequestQueue;

defined('ABSPATH') || exit;

/**
 * FailedRequest Class
 */
class FailedRequest
{
    public const FAILED_STATUS = "failed";

    public const SUCCESS_STATUS = "success";

    public const MAX_ATTEMPTS = 3;

    /**
     * The id of the corresponding woo-commerce order.
     *
     * @var int $orderId id
     */
    private $orderId = null;

    /**
     * The status of the request.
     *
     * @var string $status 'failed' or 'success'
     */
    private $status = null;

    /**
     * The number of failed attempts.
     *
     * @var integer $failedAttempts
     */
    private $failedAttempts;

    /**
     * The constructor.
     *
     * @param integer $id id for the request.
     * @param integer $orderId WooCommerce order id.
     * @param string  $status 'failed' or 'success'.
     * @param integer $failedAttempts initially 0.
     */
    public function __construct(int $id, int $orderId, string $status = 'failed', int $failedAttempts = 0)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->status = $status;
        $this->failedAttempts = $failedAttempts;
    }

    /**
     * Increments the retry-counter and returns the updated value.
     *
     * @return void
     */
    public function incrementFailedAttempts(): void
    {
        $this->failedAttempts++;
    }

    /**
     * Updated the status of the instance.
     *
     * @param string $status new status.
     * @return void
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * Returns the id of the failed request.
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns the ID of the woo-commerce order.
     *
     * @return integer
     */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * Returns the status of the request.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Returns the number of failed attempts.
     *
     * @return integer
     */
    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }
}
