<?php

use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use Mockery\MockInterface;


uses(\Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration::class);

beforeEach(function () {
    /** @var MockInterface|SettingsRepository */
    $this->settingsRepository = Mockery::mock(SettingsRepository::class);
    $this->settingsRepository->allows([
        "getSiteUrl" => "http://test.com",
        "getRestUrl" => "http://test.com/wp-json/",
        "getCustomerId" => 420000,
    ]);

    $this->logisticsOrderFactory = new LogisticsOrderFactory($this->settingsRepository);

    /** @var MockInterface|stdClass */
    $product = Mockery::mock("WC_Product");
    $product->allows([
        "get_sku" => "234",
    ]);

    $orderItemMethods = [
        "get_id" => 123,
        "get_product" => $product,
        "get_quantity" => 4,
    ];

    /** @var MockInterface|stdClass */
    $orderItem1 = Mockery::mock("WC_Order_Item_Product");
    $orderItem1->allows($orderItemMethods);

    /** @var MockInterface|stdClass */
    $orderItem2 = Mockery::mock("WC_Order_Item_Product");
    $orderItem2->allows($orderItemMethods);

    // WooCommerce returns the order items as array<string, WC_Order_Item>
    $this->wooCommerceMockOrderItems = [
        "1" => $orderItem1,
        "3" => $orderItem2
    ];

    $this->wooCommerceOrderMockMethods = [
        "get_id" => 12,
        "get_date_created" => new \WC_DateTime("2021-07-29T14:53:52+00:00"),
        "get_shipping_first_name" => "first-name",
        "get_shipping_last_name" => "last-name",
        "get_shipping_company" => "company",
        "get_shipping_address_1" => "address-1",
        "get_shipping_address_2" => "address-2",
        "get_shipping_city" => "city",
        "get_shipping_postcode" => "zip-code",
        "get_shipping_country" => "country",
        "get_shipping_state" => "state",
        "get_billing_email" => "test@company.com",
        "get_billing_phone" => "123456789",
        "get_items" => $this->wooCommerceMockOrderItems,
        "get_customer_note" => "note",
    ];
});

test('it adds the customer order to the payload', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder());

    expect($logisticsOrder->getLogisticsOrder()->getCustomerOrder())->toBe("12");
});

test('it adds the success callback url to the payload', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder());

    expect($logisticsOrder->getCallbacks()->getSuccessCallback())->toBe("http://test.com/wp-json/gebrueder-weiss-woocommerce/v1/orders/12/callbacks/success");
});

test('it adds the fulfillment callback url to the payload', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder());

    expect($logisticsOrder->getCallbacks()->getFulfillmentCallback())->toBe("http://test.com/wp-json/gebrueder-weiss-woocommerce/v1/orders/12/callbacks/fulfillment");
});

test('it adds the created date to the logistics order', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    expect($logisticsOrder->getCreationDateTime()->format(DateTimeInterface::RFC3339))->toBe("2021-07-29T14:53:52+00:00");
});

test('it adds the owner id to the logistics order', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    expect($logisticsOrder->getCustomerId())->toBe(420000);
});

test('it correctly adds the consignee address', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    $logisticsOrder->getLogisticsAddresses();
    $address = $logisticsOrder->getLogisticsAddresses()[0];

    expect($address->getAddressType())->toBe("CONSIGNEE");
    expect($address->getAddress()->getName1())->toBe("first-name");
    expect($address->getAddress()->getName2())->toBe("last-name");
    expect($address->getAddress()->getName3())->toBe("company");
    expect($address->getAddress()->getStreet1())->toBe("address-1");
    expect($address->getAddress()->getStreet2())->toBe("address-2");
    expect($address->getAddress()->getCity())->toBe("city");
    expect($address->getAddress()->getZipCode())->toBe("zip-code");
    expect($address->getAddress()->getCountryCode())->toBe("country");
    expect($address->getAddress()->getState())->toBe("state");
});

test('it correctly adds the consignee contact', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    $logisticsOrder->getLogisticsAddresses();
    $address = $logisticsOrder->getLogisticsAddresses()[0];

    expect($address->getContact()->getName())->toBe("first-name last-name");
    expect($address->getContact()->getEmail())->toBe("test@company.com");
    expect($address->getContact()->getPhone())->toBe("123456789");
});

test('it correctly creates the consignee contact name if only one name is available', function () {
    $orderMethods = $this->wooCommerceOrderMockMethods;
    $orderMethods["get_shipping_first_name"] = null;

    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder($orderMethods))->getLogisticsOrder();

    $logisticsOrder->getLogisticsAddresses();
    $address = $logisticsOrder->getLogisticsAddresses()[0];

    expect($address->getContact()->getName())->toBe("last-name");
});

test('it correctly adds the orderby address', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    $logisticsOrder->getLogisticsAddresses();
    $address = $logisticsOrder->getLogisticsAddresses()[1];

    expect($address->getAddressType())->toBe("ORDERBY");
});

test('it adds the correct qualifier to the orderby address', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    $logisticsOrder->getLogisticsAddresses();
    $address = $logisticsOrder->getLogisticsAddresses()[1];

    expect($address->getAddressReferences()[0]->getReference())->toBe("420000");
});

test('it adds the custom id to the orderby address', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    $logisticsOrder->getLogisticsAddresses();
    $address = $logisticsOrder->getLogisticsAddresses()[1];

    expect($address->getAddressReferences()[0]->getQualifier())->toBe("CUSTOMER_ID");
});

test('it builds an order line for each article', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    expect($logisticsOrder->getOrderLines())->toHaveCount(2);
});

test('it ensures that the order line array has proper keys', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();

    expect($logisticsOrder->getOrderLines())->toHaveKey(0);
    expect($logisticsOrder->getOrderLines())->toHaveKey(1);
});

test('it converts woocommerce order items into articles', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();
    $orderLine = $logisticsOrder->getOrderLines()[0];

    expect($orderLine->getArticleId())->toBe("234");
    expect($orderLine->getLineItemNumber())->toBe(123);
    expect($orderLine->getQuantity())->toBe(4);
});

test('it adds one delivery note to the article', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();
    $article = $logisticsOrder->getOrderLines()[0];

    expect($article->getNotes())->toHaveCount(1);
    expect($article->getNotes()[0]->getNoteType())->toBe("DELIVERYNOTE");
});

test('it adds the customer message to the delivery note', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();
    $article = $logisticsOrder->getOrderLines()[0];

    expect($article->getNotes()[0]->getNoteText()->getLanguage())->toBe("en-US");
    expect($article->getNotes()[0]->getNoteText()->getText())->toBe("note");
});

test('it adds logistics requirements', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();
    $logisticsRequirement = $logisticsOrder->getLogisticsRequirements();

    expect($logisticsRequirement->getLogisticsProduct())->not->toBeNull();
});

test('it adds a logistics product', function () {
    $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder(createMockOrder())->getLogisticsOrder();
    $product = $logisticsOrder->getLogisticsRequirements()->getLogisticsProduct();

    expect($product->getProduct())->toBe("OUTBOUND_DELIVERY");
    expect($product->getProductServiceLevel())->toBe("STANDARD");
});

function createMockOrder(?array $mockMethods = null)
{
    /** @var MockInterface|stdClass */
    $order = Mockery::mock("WC_Order");
    $order->allows($mockMethods ?? $this->wooCommerceOrderMockMethods);

    return $order;
}
