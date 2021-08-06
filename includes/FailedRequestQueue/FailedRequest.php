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

use DateTime;

/**
 * FailedRequest Class
 */
class FailedRequest
{
    public const FAILED_STATUS = "failed";

    public const SUCCESS_STATUS = "success";

    public const MAX_ATTEMPTS = 3;

    /**
     * The id of the corresponding WooCommerce order.
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
     * Date of the last attempt of sending the request
     *
     * @var DateTime
     */
    private $lastAttemptDate;

    /**
     * The constructor.
     *
     * @param integer  $id id for the request.
     * @param integer  $orderId WooCommerce order id.
     * @param string   $status 'failed' or 'success'.
     * @param integer  $failedAttempts initially 0.
     * @param DateTime $lastAttemptDate initially now.
     */
    public function __construct(int $id, int $orderId, string $status = 'failed', int $failedAttempts = 0, DateTime $lastAttemptDate = null)
    {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->status = $status;
        $this->failedAttempts = $failedAttempts;
        $this->lastAttemptDate = $lastAttemptDate ?? new DateTime();
    }

    /**
     * Increments the failed attempts counter.
     *
     * @return void
     */
    public function incrementFailedAttempts(): void
    {
        $this->failedAttempts++;
    }

    /**
     * Sets the last attempted date to now.
     *
     * @return void
     */
    public function setLastAttemptedDateToNow(): void
    {
        $this->lastAttemptDate = new DateTime();
    }

    /**
     * Updated the status of the failed request.
     *
     * @param string $status The new status.
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
     * Returns the id of the WooCommerce order.
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

    /**
     * Returns the date when the last attempt for sending the request happened.
     *
     * @return DateTime
     */
    public function getLastAttemptDate(): DateTime
    {
        return $this->lastAttemptDate;
    }
}
