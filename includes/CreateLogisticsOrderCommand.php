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
     */
    public function execute(): void
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->wooCommerceOrder);

        try {
            $this->writeApi->logisticsOrderPost($logisticsOrder);
            $this->wooCommerceOrder->set_status("on-hold");
            $this->wooCommerceOrder->save();
        } catch (ApiException $exception) {
            if ($exception->getCode() === 400) {
                // handle faulty parameters.
                return;
            }

            if ($exception->getCode() === 409) {
                // handle conflict.
                return;
            }

            // retry request.
        }
    }
}
