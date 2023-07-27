<?php

uses(\WP_UnitTestCase::class);
use Mockery\MockInterface;
use Towa\GebruederWeissSDK\ApiException;
use Towa\GebruederWeissSDK\Api\DefaultApi;
use Towa\GebruederWeissSDK\Model\InlineObject as CreateLogisticsOrderPayload;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\CreateLogisticsOrderCommand;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderConflictException;
use Towa\GebruederWeissWooCommerce\Exceptions\CreateLogisticsOrderFailedException;

uses(\Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration::class);

private const STATUS_ON_HOLD = "on-hold";

private const HTTP_STATUS_CONFLICT = 409;

beforeEach(function () {
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
});

/** @throws CreateLogisticsOrderFailedException */
test('it updates the order state after a successful api request', function () {
    (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $this->gebruederWeissApi))
        ->execute(self::STATUS_ON_HOLD);

    $this->order->shouldHaveReceived("set_status", [self::STATUS_ON_HOLD]);
    $this->order->shouldHaveReceived("save");
});

test('it does not update the order state after a failed request', function () {
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
});

/** @throws CreateLogisticsOrderFailedException */
test('it throws an exception if the api call fails', function () {
    $this->expectException(CreateLogisticsOrderFailedException::class);

    /** @var MockInterface|DefaultApi $gebruederWeissApi */
    $gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $gebruederWeissApi
        ->shouldReceive("logisticsOrderPost")
        ->andThrow(new ApiException("Unauthenticated", 401));

    (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $gebruederWeissApi))
        ->execute(self::STATUS_ON_HOLD);
});

/** @throws CreateLogisticsOrderFailedException */
test('it throws a conflict exception if the api returns a conflict', function () {
    $this->expectException(CreateLogisticsOrderConflictException::class);

    /** @var MockInterface|DefaultApi $gebruederWeissApi */
    $gebruederWeissApi = Mockery::mock(DefaultApi::class);
    $gebruederWeissApi
        ->shouldReceive("logisticsOrderPost")
        ->andThrow(new ApiException("Conflict", self::HTTP_STATUS_CONFLICT));

    (new CreateLogisticsOrderCommand($this->order, $this->logisticsOrderFactory, $gebruederWeissApi))
        ->execute(self::STATUS_ON_HOLD);
});
