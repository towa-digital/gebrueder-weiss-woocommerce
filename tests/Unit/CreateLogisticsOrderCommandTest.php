<?php

namespace Tests\Unit;

use Exception;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use stdClass;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Api\DefaultApi;
use Towa\GebruederWeissSDK\Model\InlineObject as CreateLogisticsOrderPayload;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\CreateLogisticsOrderCommand;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderConflictException;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderFailedException;
use WC_Order;

class CreateLogisticsOrderCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const STATUS_ON_HOLD = "on-hold";

    /** @var MockInterface|DefaultApi */
    private $gebruederWeissApi;

    /** @var MockInterface|LogisticsOrderFactory */
    private $logisticsOrderFactory;

    /** @var MockInterface|stdClass */
    private $order;

    public function setUp(): void
    {
        parent::setUp();

        $this->gebruederWeissApi = Mockery::mock(DefaultApi::class);
        $this->gebruederWeissApi->allows("logisticsOrderPost");

        $this->logisticsOrderFactory = Mockery::mock(LogisticsOrderFactory::class);
        $this->logisticsOrderFactory->allows([
            "buildFromWooCommerceOrder" => new CreateLogisticsOrderPayload(),
        ]);

        $this->order = Mockery::mock(WC_Order::class);
        $this->order->allows([
            "set_status" => null,
            "save"       => null,
            "get_id"     => 42
        ]);
    }

    /** @throws CreateLogisticsOrderFailedException */
    public function test_it_updates_the_order_state_after_a_successful_api_request()
    {
        (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $this->gebruederWeissApi))
            ->execute(self::STATUS_ON_HOLD);

        $this->order->shouldHaveReceived("set_status", [self::STATUS_ON_HOLD]);
        $this->order->shouldHaveReceived("save");
    }

    public function test_it_does_not_update_the_order_state_after_a_failed_request()
    {
        /** @var MockInterface|DefaultApi $gebruederWeissApi */
        $gebruederWeissApi = Mockery::mock(DefaultApi::class);
        $gebruederWeissApi
            ->shouldReceive("logisticsOrderPost")
            ->andThrow(new ApiException("Unauthenticated", 401));

        try {
            (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $gebruederWeissApi))->execute(self::STATUS_ON_HOLD);
        } catch (Exception $e) {
        }

        $this->order->shouldNotHaveReceived("save");
    }

    /** @throws CreateLogisticsOrderFailedException */
    public function test_it_throws_an_exception_if_the_api_call_fails()
    {
        $this->expectException(CreateLogisticsOrderFailedException::class);

        /** @var MockInterface|DefaultApi $gebruederWeissApi */
        $gebruederWeissApi = Mockery::mock(DefaultApi::class);
        $gebruederWeissApi
            ->shouldReceive("logisticsOrderPost")
            ->andThrow(new ApiException("Unauthenticated", 401));

        (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $gebruederWeissApi))
            ->execute(self::STATUS_ON_HOLD);
    }

    /** @throws CreateLogisticsOrderFailedException */
    public function test_it_throws_a_conflict_exception_if_the_api_returns_a_conflict()
    {
        $this->expectException(CreateLogisticsOrderConflictException::class);

        /** @var MockInterface|DefaultApi $gebruederWeissApi */
        $gebruederWeissApi = Mockery::mock(DefaultApi::class);
        $gebruederWeissApi
            ->shouldReceive("logisticsOrderPost")
            ->andThrow(new ApiException("Conflict", 409));

        (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $gebruederWeissApi))
            ->execute(self::STATUS_ON_HOLD);
    }
}
