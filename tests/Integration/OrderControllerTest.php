<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\OrderController;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use Mockery\MockInterface;
use Towa\GebruederWeissWooCommerce\OrderRepository;

uses(\Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration::class);

const ORDER_ID          = 1;

const NEW_ORDER_ID      = 1234567890;

const TRACKING_URL      = 'http://example.com';

const TRANSPORT_PRODUCT = 'DHL';

beforeEach(function () {
    self::$settingsRepository = Mockery::mock(SettingsRepository::class);
    self::$request            = Mockery::mock(WP_REST_Request::class);
    self::$orderRepository    = Mockery::mock(OrderRepository::class);
    self::$order              = Mockery::mock(WC_Order::class);
    self::$orderController    = new OrderController(self::$settingsRepository, self::$orderRepository);

    self::$request
        ->allows('get_params')
        ->andReturn(['id' => self::ORDER_ID]);
});

test('the success callback returns a 404 response if no order with the given id exists', function () {
    self::$request
        ->allows('get_body')
        ->andReturn(json_encode(['orderId' => self::ORDER_ID]));

    self::$settingsRepository
        ->allows();

    self::$orderRepository
        ->allows(['findById' => null]);

    expect(self::successCallback()->get_status())->toEqual(404);
});

test('the success callback returns a 200 response if a order with the given id exists', function () {
    self::$request
        ->allows('get_body')
        ->andReturn(json_encode(['orderId' => self::ORDER_ID]));

    self::$settingsRepository
        ->allows([
            'getFulfilledState'   => 'wc-fulfilled',
            'getOrderIdFieldName' => 'order_id'
        ]);

    self::$order->allows('get_status');
    self::$order->allows('update_meta_data');
    self::$order->allows('save');

    self::$orderRepository
        ->allows(['findById' => self::$order]);

    expect(self::successCallback()->get_status())->toEqual(200);
});

test('the success callback updates the meta order id', function () {
    self::$request
        ->allows('get_body')
        ->andReturn(json_encode(['orderId' => self::NEW_ORDER_ID]));

    self::$settingsRepository
        ->allows([
            'getFulfilledState'   => 'wc-fulfilled',
            'getOrderIdFieldName' => 'order_id'
        ]);

    self::$order->allows('get_status');
    self::$order->allows('update_meta_data');
    self::$order->allows('save');

    self::$orderRepository
        ->allows(['findById' => self::$order]);

    self::successCallback();

    self::$order->shouldHaveReceived('update_meta_data', ['order_id', 1234567890]);
    self::$order->shouldHaveReceived('save');
});

test('the fulfillment callback updates the woocommerce order status', function () {
    self::$request
        ->allows('get_body')
        ->andReturn(
            json_encode([
                'orderId'          => self::NEW_ORDER_ID,
                'trackingUrl'      => self::TRACKING_URL,
                'transportProduct' => self::TRANSPORT_PRODUCT
            ])
        );

    self::$settingsRepository
        ->allows([
            'getFulfilledState'              => 'wc-fulfilled',
            'getTrackingLinkFieldName'       => 'tracking_id',
            'getCarrierInformationFieldName' => 'carrier_information'
        ]);

    self::$order->allows('set_status');
    self::$order->allows('get_status');
    self::$order->allows('update_meta_data');
    self::$order->allows('save');

    self::$orderRepository
        ->allows(['findById' => self::$order]);

    self::fulfillmentCallback();

    self::$order->shouldHaveReceived('set_status', ['wc-fulfilled']);
    self::$order->shouldHaveReceived('save');
});

test('the fulfillment callback updates the order meta data', function () {
    self::$request
        ->allows('get_body')
        ->andReturn(
            json_encode([
                'orderId'          => self::NEW_ORDER_ID,
                'trackingUrl'      => self::TRACKING_URL,
                'transportProduct' => self::TRANSPORT_PRODUCT
            ])
        );

    self::$settingsRepository
        ->allows([
            'getFulfilledState'              => 'wc-fulfilled',
            'getTrackingLinkFieldName'       => 'tracking_id',
            'getCarrierInformationFieldName' => 'carrier_information'
        ]);

    self::$order->allows('set_status');
    self::$order->allows('get_status');
    self::$order->allows('update_meta_data');
    self::$order->allows('save');

    self::$orderRepository
        ->allows(['findById' => self::$order]);

    self::fulfillmentCallback();

    self::$order->shouldHaveReceived('update_meta_data', ['tracking_id', 'http://example.com']);
    self::$order->shouldHaveReceived('update_meta_data', ['carrier_information', 'DHL']);
});

test('the fulfillment callback does not create tracking url meta if empty string is in request', function () {
    self::$request
        ->allows('get_body')
        ->andReturn(
            json_encode([
                'orderId'          => self::NEW_ORDER_ID,
                'trackingUrl'      => '',
                'transportProduct' => self::TRANSPORT_PRODUCT
            ])
        );

    self::$settingsRepository
        ->allows([
            'getFulfilledState'              => 'wc-fulfilled',
            'getTrackingLinkFieldName'       => 'tracking_id',
            'getCarrierInformationFieldName' => 'carrier_information'
        ]);

    self::$order->allows('set_status');
    self::$order->allows('get_status');
    self::$order->allows('update_meta_data');
    self::$order->allows('save');

    self::$orderRepository
        ->allows(['findById' => self::$order]);

    self::fulfillmentCallback();

    self::$order->shouldNotHaveReceived('update_meta_data', ['tracking_id', '']);
    self::$order->shouldHaveReceived('update_meta_data', ['carrier_information', 'DHL']);
});

test('the fulfillment callback returns 200 on success', function () {
    self::$request
        ->allows('get_body')
        ->andReturn(
            json_encode([
                'orderId'          => self::NEW_ORDER_ID,
                'trackingUrl'      => self::TRACKING_URL,
                'transportProduct' => self::TRANSPORT_PRODUCT
            ])
        );

    self::$settingsRepository
        ->allows([
            'getFulfilledState'              => 'wc-fulfilled',
            'getTrackingLinkFieldName'       => 'tracking_id',
            'getCarrierInformationFieldName' => 'carrier_information'
        ]);

    self::$order->allows('set_status');
    self::$order->allows('get_status');
    self::$order->allows('update_meta_data');
    self::$order->allows('save');

    self::$orderRepository
        ->allows(['findById' => self::$order]);

    expect(self::fulfillmentCallback()->get_status())->toEqual(200);
});

test('the fulfillment callback returns 404 if the order cannot be found', function () {
    self::$request
        ->allows('get_body')
        ->andReturn(
            json_encode([
                'orderId'          => self::ORDER_ID,
                'trackingUrl'      => self::TRACKING_URL,
                'transportProduct' => self::TRANSPORT_PRODUCT
            ])
        );

    self::$orderRepository->allows(['findById' => null]);

    expect(self::fulfillmentCallback()->get_status())->toEqual(404);
});

function fulfillmentCallback() : WP_REST_Response
{
    return self::$orderController->handleFulfillmentCallback(self::$request);
}

function successCallback() : WP_REST_Response
{
    return self::$orderController->handleSuccessCallback(self::$request);
}
