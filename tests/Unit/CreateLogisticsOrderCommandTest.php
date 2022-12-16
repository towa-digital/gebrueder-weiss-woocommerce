<?php

namespace Tests\Unit;

use Exception;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

use PHPUnit\Framework\TestCase;

use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Api\DefaultApi;
use Towa\GebruederWeissSDK\Model\InlineObject as CreateLogisticsOrderPayload;
use Towa\GebruederWeissSDK\Model\LogisticsOrder;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\CreateLogisticsOrderCommand;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderConflictException;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderFailedException;
use Towa\GebruederWeissWooCommerce\SettingsRepository;

use stdClass;
use WC_Order;

class CreateLogisticsOrderCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MockInterface|DefaultApi */
    private $gebruederWeissApi;

    /** @var MockInterface|LogisticsOrderFactory */
    private $logisticsOrderFactory;

    /** @var MockInterface|stdClass */
    private $order;

    /** @var string|null  */
    private $pendingState;


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
            "save" => null,
            "get_id" => 42
        ]);

        $this->pendingState = Mockery::mock(SettingsRepository::class)->getPendingState();
    }


    public function test_it_updates_the_order_state_after_a_successful_api_request()
    {
        (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $this->gebruederWeissApi))
            ->execute($this->pendingState);

        $this->order->shouldHaveReceived("set_status", [$this->pendingState]);
        $this->order->shouldHaveReceived("save");
    }

    public function test_it_does_not_update_the_order_state_after_a_failed_request()
    {
        $this->gebruederWeissApi
            ->shouldReceive("logisticsOrderPost")
            ->andThrow(new ApiException("Unauthenticated", 401));

        try {
            (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $this->gebruederWeissApi))
                ->execute($this->pendingState);
        } catch (Exception $e) {
        }

        $this->order->shouldNotHaveReceived("save");
    }

    public function test_it_throws_an_exception_if_the_api_call_fails()
    {
        $this->expectException(CreateLogisticsOrderFailedException::class);

        $this->gebruederWeissApi
            ->shouldReceive("logisticsOrderPost")
            ->andThrow(new ApiException("Unauthenticated", 401));

        (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $this->gebruederWeissApi))
            ->execute($this->pendingState);
    }

    public function test_it_throws_a_conflict_exception_if_the_api_returns_a_conflict()
    {
        $this->expectException(CreateLogisticsOrderConflictException::class);

        $this->gebruederWeissApi
            ->shouldReceive("logisticsOrderPost")
            ->andThrow(new ApiException("Conflict", 409));

        (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $this->gebruederWeissApi))
            ->execute($this->pendingState);
    }
}
