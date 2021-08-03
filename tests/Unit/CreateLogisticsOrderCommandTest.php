<?php

namespace Tests\Unit;

use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Api\WriteApi;
use Towa\GebruederWeissSDK\Model\LogisticsOrder;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\CreateLogisticsOrderCommand;

class CreateLogisticsOrderCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MockInterface|WriteApi */
    private $writeApi;

    /** @var MockInterface|LogisticsOrderFactory */
    private $logisticsOrderFactory;

    public function setUp(): void
    {
        parent::setUp();

        /** @var MockInterface|WriteApi */
        $this->writeApi = Mockery::mock(WriteApi::class);
        $this->writeApi->allows("logisticsOrderPost");

        /** @var MockInterface|LogisticsOrderFactory */
        $this->logisticsOrderFactory = Mockery::mock(LogisticsOrderFactory::class);
        $this->logisticsOrderFactory->allows([
            "buildFromWooCommerceOrder" => new LogisticsOrder(),
        ]);
    }


    public function test_it_updates_the_order_state_after_a_successful_api_request()
    {
        /** @var MockInterface|stdClass */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("save");

        (new CreateLogisticsOrderCommand($order, $this->logisticsOrderFactory, $this->writeApi))->execute();

        $order->shouldHaveReceived("save");
    }

    public function test_it_does_not_update_the_order_state_after_a_failed_request()
    {
        /** @var MockInterface|WriteApi */
        $writeApi = Mockery::mock(WriteApi::class);
        $writeApi->shouldReceive("logisticsOrderPost")->andThrow(new ApiException("Unauthenticated", 401));

        /** @var MockInterface|stdClass */
        $order = Mockery::mock("WC_Order");
        $order->allows("set_status");
        $order->allows("save");

        (new CreateLogisticsOrderCommand($order, $this->logisticsOrderFactory, $writeApi))->execute();

        $order->shouldNotHaveReceived("save");
    }
}
