<?php
/**
 * Failed Request
 *
 * Holds all information on a failed request against the Gebrueder Weiss API.
 *
 * @package RequestQueue
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

/**
 * FailedRequest Class
 */
class FailedRequest
{
    /**
     * The id of the corresponding woo-commerce order.
     *
     * @var int $orderId id
     */
    private $orderID = null;

    /**
     * The status of the request.
     *
     * @var string $status 'failed' or 'sent'
     */
    private $status = null;

    /**
     * The number of attempted sendings.
     *
     * @var integer $counter maximum of 3.
     */
    private $counter = 0;

    /**
     * The constructor.
     *
     * @param integer $id woo-commerce order id.
     * @param string  $status 'failed' or 'sent'.
     * @param integer $counter initially 0.
     */
    public function __construct(int $id, string $status = 'failed', int $counter = 0)
    {
        $this->id = $id;
        $this->status = $status;
        $this->counter = $counter;
    }

    /**
     * Increments the retry-counter and returns the updated value.
     *
     * @return integer
     */
    public function incrementCounter(): int
    {
        return ++$this->counter;
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
     * Returns the ID of the woo-commerce order.
     *
     * @return integer
     */
    public function getOrderId(): int
    {
        return $this->orderID;
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
     * Returns the number of attempts.
     *
     * @return integer
     */
    public function getCounter(): int
    {
        return $this->counter;
    }
}
