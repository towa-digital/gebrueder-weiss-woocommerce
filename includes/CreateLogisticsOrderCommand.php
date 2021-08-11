<?php
/**
 * Create Logistics Order Command
 *
 * Creates a logistics order at Gebrueder Weiss and updates the state of the WooCommerce order.
 *
 * @package FailedRequestQueue
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

use Towa\GebruederWeissSDK\Api\WriteApi;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderConflictException;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderFailedException;

/**
 * Create Logistics Order Command
 *
 * Creates a logistics order at Gebrueder Weiss and updates the state of the WooCommerce order.
 */
class CreateLogisticsOrderCommand
{
    /**
     * Gebrueder Weiss Write API client
     *
     * @var WriteApi
     */
    private $writeApi;

    /**
     * WooCommerce Order to be processed
     *
     * @var object
     */
    private $wooCommerceOrder;

    /**
     * Logistics order factory
     *
     * @var LogisticsOrderFactory
     */
    private $logisticsOrderFactory;

    /**
     * Creates a new command
     *
     * @param object                $wooCommerceOrder WooCommerce order.
     * @param LogisticsOrderFactory $logisticsOrderFactory Factory for creating logistics orders.
     * @param WriteApi              $writeApi Gebrueder Weiss Write API client.
     */
    public function __construct(object $wooCommerceOrder, LogisticsOrderFactory $logisticsOrderFactory, WriteApi $writeApi)
    {
        $this->writeApi = $writeApi;
        $this->wooCommerceOrder = $wooCommerceOrder;
        $this->logisticsOrderFactory = $logisticsOrderFactory;
    }

    /**
     * Executes the command
     *
     * @return void
     * @throws CreateLogisticsOrderConflictException Thrown if there was a conflict while creating the order.
     * @throws CreateLogisticsOrderFailedException Thrown if something went wrong.
     */
    public function execute(): void
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->wooCommerceOrder);

        try {
            $this->writeApi->logisticsOrderPost($logisticsOrder);
            $this->wooCommerceOrder->set_status("on-hold");
            $this->wooCommerceOrder->save();
        } catch (ApiException $e) {
            if ($e->getCode() === 409) {
                throw new CreateLogisticsOrderConflictException("Could not create logistics order due to conflict: " . $e->getMessage());
            }

            throw new CreateLogisticsOrderFailedException("Could not create logistics order: " . $e->getMessage());
        }
    }
}
