<?php

namespace Tests\Unit;

use DateTimeInterface;
use Towa\GebruederWeissWooCommerce\LogisticsOrderFactory;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;

class LogisticsOrderFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MockInterface|SettingsRepository */
    private $settingsRepository;

    /** @var LogisticsOrderFactory */
    private $logisticsOrderFactory;

    /** @var array */
    private $wooCommerceOrderMockMethods;

    /** @var array */
    private $wooCommerceMockOrderItems;

    public function setUp(): void
    {
        /** @var MockInterface|SettingsRepository */
        $this->settingsRepository = Mockery::mock(SettingsRepository::class);
        $this->settingsRepository->allows([
            "getSiteUrl" => "http://test.com",
            "getHomeUrl" => "http://test.com/wp",
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
    }

    public function test_it_adds_the_customer_order_to_the_payload()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $this->assertSame("12", $logisticsOrder->getLogisticsOrder()->getCustomerOrder());
    }


    public function test_it_adds_the_success_callback_url_to_the_payload()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $this->assertSame("http://test.com/wp/wp-json/gebrueder-weiss-woocommerce/v1/orders/12/callbacks/success", $logisticsOrder->getCallbacks()->getSuccessCallback());
    }

    public function test_it_adds_the_fulfillment_callback_url_to_the_payload()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $this->assertSame("http://test.com/wp/wp-json/gebrueder-weiss-woocommerce/v1/orders/12/callbacks/fulfillment", $logisticsOrder->getCallbacks()->getFulfillmentCallback());
    }

    public function test_it_adds_the_created_date_to_the_logistics_order()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $this->assertSame("2021-07-29T14:53:52+00:00", $logisticsOrder->getCreationDateTime()->format(DateTimeInterface::RFC3339));
    }

    public function test_it_adds_the_owner_id_to_the_logistics_order()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $this->assertSame(420000, $logisticsOrder->getCustomerId());
    }

    public function test_it_correctly_adds_the_consignee_address()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[0];

        $this->assertSame("CONSIGNEE", $address->getAddressType());
        $this->assertSame("first-name", $address->getAddress()->getName1());
        $this->assertSame("last-name", $address->getAddress()->getName2());
        $this->assertSame("company", $address->getAddress()->getName3());
        $this->assertSame("address-1", $address->getAddress()->getStreet1());
        $this->assertSame("address-2", $address->getAddress()->getStreet2());
        $this->assertSame("city", $address->getAddress()->getCity());
        $this->assertSame("zip-code", $address->getAddress()->getZipCode());
        $this->assertSame("country", $address->getAddress()->getCountryCode());
        $this->assertSame("state", $address->getAddress()->getState());
    }

    public function test_it_correctly_adds_the_consignee_contact()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[0];

        $this->assertSame("first-name last-name", $address->getContact()->getName());
        $this->assertSame("test@company.com", $address->getContact()->getEmail());
        $this->assertSame("123456789", $address->getContact()->getPhone());
    }

    public function test_it_correctly_creates_the_consignee_contact_name_if_only_one_name_is_available()
    {
        $orderMethods = $this->wooCommerceOrderMockMethods;
        $orderMethods["get_shipping_first_name"] = null;

        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder($orderMethods))->getLogisticsOrder();

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[0];

        $this->assertSame("last-name", $address->getContact()->getName());
    }

    public function test_it_correctly_adds_the_orderby_address()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[1];

        $this->assertSame("ORDERBY", $address->getAddressType());
    }

    public function test_it_adds_the_correct_qualifier_to_the_orderby_address()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[1];

        $this->assertSame("420000", $address->getAddressReferences()[0]->getReference());
    }

    public function test_it_adds_the_custom_id_to_the_orderby_address()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[1];

        $this->assertSame("CUSTOMER_ID", $address->getAddressReferences()[0]->getQualifier());
    }

    public function test_it_builds_an_order_line_for_each_article()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $this->assertCount(2, $logisticsOrder->getOrderLines());
    }

    public function test_it_ensures_that_the_order_line_array_has_proper_keys()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();

        $this->assertArrayHasKey(0, $logisticsOrder->getOrderLines());
        $this->assertArrayHasKey(1, $logisticsOrder->getOrderLines());
    }

    public function test_it_converts_woocommerce_order_items_into_articles()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();
        $orderLine = $logisticsOrder->getOrderLines()[0];

        $this->assertSame("234", $orderLine->getArticleId());
        $this->assertSame(123, $orderLine->getLineItemNumber());
        $this->assertSame(4, $orderLine->getQuantity());
    }

    public function test_it_adds_one_delivery_note_to_the_article()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();
        $article = $logisticsOrder->getOrderLines()[0];

        $this->assertCount(1, $article->getNotes());
        $this->assertSame("DELIVERYNOTE", $article->getNotes()[0]->getNoteType());
    }

    public function test_it_adds_the_customer_message_to_the_delivery_note()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();
        $article = $logisticsOrder->getOrderLines()[0];

        $this->assertSame("en-US", $article->getNotes()[0]->getNoteText()->getLanguage());
        $this->assertSame("note", $article->getNotes()[0]->getNoteText()->getText());
    }

    public function test_it_adds_logistics_requirements()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();
        $logisticsRequirement = $logisticsOrder->getLogisticsRequirements();

        $this->assertNotNull($logisticsRequirement->getLogisticsProduct());
    }

    public function test_it_adds_a_logistics_product()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder())->getLogisticsOrder();
        $product = $logisticsOrder->getLogisticsRequirements()->getLogisticsProduct();

        $this->assertSame("OUTBOUND_DELIVERY", $product->getProduct());
        $this->assertSame("STANDARD", $product->getProductServiceLevel());
    }

    private function createMockOrder(?array $mockMethods = null)
    {
        /** @var MockInterface|stdClass */
        $order = Mockery::mock("WC_Order");
        $order->allows($mockMethods ?? $this->wooCommerceOrderMockMethods);

        return $order;
    }
}
