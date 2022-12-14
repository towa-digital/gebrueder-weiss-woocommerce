<?php

namespace Tests\Unit;

use Towa\GebruederWeissWooCommerce\OrderController;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use Mockery;
use Mockery\MockInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Towa\GebruederWeissWooCommerce\OrderRepository;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;

class OrderControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MockInterface|OrderRepository */
    private static $orderRepository;

    /** @var MockInterface|WP_REST_Request */
    private static $request;

    /** @var MockInterface|SettingsRepository */
    private static $settingsRepository;

    /** @var MockInterface|WC_Order */
    private static $order;

    /** @var OrderController */
    private static $orderController;

    private const ORDER_ID          = 1;
    private const NEW_ORDER_ID      = 1234567890;
    private const TRACKING_URL      = 'http://example.com';
    private const TRANSPORT_PRODUCT = 'DHL';

    protected function setUp(): void
    {
        self::$settingsRepository = Mockery::mock(SettingsRepository::class);
        self::$request            = Mockery::mock(WP_REST_Request::class);
        self::$orderRepository    = Mockery::mock(OrderRepository::class);
        self::$order              = Mockery::mock(WC_Order::class);
        self::$orderController    = new OrderController(self::$settingsRepository, self::$orderRepository);

        self::$request
            ->allows('get_params')
            ->andReturn(['id' => self::ORDER_ID]);
    }


    public function test_the_success_callback_returns_a_404_response_if_no_order_with_the_given_id_exists(): void
    {
        self::$request
            ->allows('get_body')
            ->andReturn(json_encode(['orderId' => self::ORDER_ID]));

        self::$settingsRepository
            ->allows();

        self::$orderRepository
            ->allows(['findById' => null]);

        $this->assertEquals(404, self::successCallback()->get_status());
    }

    public function test_the_success_callback_returns_a_200_response_if_a_order_with_the_given_id_exists(): void
    {
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

        $this->assertEquals(200, self::successCallback()->get_status());
    }

    public function test_the_success_callback_updates_the_meta_order_id()
    {
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
    }


    public function test_the_fulfillment_callback_updates_the_woocommerce_order_status(): void
    {
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
    }

    public function test_the_fulfillment_callback_updates_the_order_meta_data(): void
    {
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
    }


    public function test_the_fulfillment_callback_does_not_create_tracking_url_meta_if_empty_string_is_in_request(): void
    {
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
    }


    public function test_the_fulfillment_callback_returns_200_on_success(): void
    {
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

        $this->assertEquals(200, self::fulfillmentCallback()->get_status());
    }


    public function test_the_fulfillment_callback_returns_404_if_the_order_cannot_be_found(): void
    {
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

        $this->assertEquals(404, self::fulfillmentCallback()->get_status());
    }


    private static function fulfillmentCallback(): WP_REST_Response
    {
        return self::$orderController->handleFulfillmentCallback(self::$request);
    }


    private static function successCallback(): WP_REST_Response
    {
        return self::$orderController->handleSuccessCallback(self::$request);
    }
}
