<?php

namespace Tests\Unit;

use DateTimeInterface;
use GbWeiss\includes\LogisticsOrderFactory;
use GbWeiss\includes\SettingsRepository;
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
            "getCustomerId" => "42",
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
        $orderItem1 = Mockery::mock("WC_Order");
        $orderItem1->allows($orderItemMethods);

        /** @var MockInterface|stdClass */
        $orderItem2 = Mockery::mock("WC_Order");
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

    public function test_it_adds_the_callback_url_to_the_logistics_order()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $this->assertSame("http://test.com/wp-json/gebrueder-weiss-woocommerce/v1/update/12", $logisticsOrder->getUrl());
    }

    public function test_it_adds_the_created_date_to_the_logistics_order()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $this->assertSame("2021-07-29T14:53:52+00:00", $logisticsOrder->getCreationDateTime()->format(DateTimeInterface::RFC3339));
    }

    public function test_it_correctly_adds_the_consignee_address()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[0];

        $this->assertSame("consignee", $address->getAddressType());
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
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

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

        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder($orderMethods));

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[0];

        $this->assertSame("last-name", $address->getContact()->getName());
    }

    public function test_it_correctly_adds_the_orderby_address()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[1];

        $this->assertSame("orderby", $address->getAddressType());
    }

    public function test_it_adds_the_correct_qualifier_to_the_orderby_address()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[1];

        $this->assertSame("42", $address->getAddressReferences()[0]->getReference());
    }

    public function test_it_adds_the_custom_id_to_the_orderby_address()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $logisticsOrder->getLogisticsAddresses();
        $address = $logisticsOrder->getLogisticsAddresses()[1];

        $this->assertSame("gwcustomerid", $address->getAddressReferences()[0]->getQualifier());
    }

    public function test_it_builds_an_order_line_for_each_article()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $this->assertCount(2, $logisticsOrder->getOrderLines());
    }

    public function test_it_ensures_that_the_order_line_array_has_proper_keys()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());

        $this->assertArrayHasKey(0, $logisticsOrder->getOrderLines());
        $this->assertArrayHasKey(1, $logisticsOrder->getOrderLines());
    }

    public function test_it_adds_one_item_per_orderline()
    {
        $order = $this->createMockOrder();
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($order);

        $order->shouldHaveReceived("get_items", ["line_item"]);
        $this->assertCount(1, $logisticsOrder->getOrderLines()[0]->getArticles());
    }

    public function test_it_converts_woocommerce_order_items_into_articles()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());
        $article = $logisticsOrder->getOrderLines()[0]->getArticles()[0];

        $this->assertSame(234, $article->getArticleId());
        $this->assertSame(123, $article->getLineItemNumber());
        $this->assertSame(4, $article->getQuantity());
    }

    public function test_it_adds_one_delivery_note_to_the_article()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());
        $article = $logisticsOrder->getOrderLines()[0]->getArticles()[0];

        $this->assertCount(1, $article->getLogisticsrequirement()->getNotes());
        $this->assertSame("deliverynotearticle", $article->getLogisticsrequirement()->getNotes()[0]->getNoteType());
    }

    public function test_it_adds_the_customer_message_to_the_delivery_note()
    {
        $logisticsOrder = $this->logisticsOrderFactory->buildFromWooCommerceOrder($this->createMockOrder());
        $article = $logisticsOrder->getOrderLines()[0]->getArticles()[0];

        $this->assertSame("de-DE", $article->getLogisticsrequirement()->getNotes()[0]->getNoteText()->getLanguage());
        $this->assertSame("note", $article->getLogisticsrequirement()->getNotes()[0]->getNoteText()->getText());
    }

    private function createMockOrder(?array $mockMethods = null)
    {
        /** @var MockInterface|stdClass */
        $order = Mockery::mock("WC_Order");
        $order->allows($mockMethods ?? $this->wooCommerceOrderMockMethods);

        return $order;
    }
}
