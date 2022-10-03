<?php
/**
 * Factory for creating Logistics Orders
 *
 * @package Plugin
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

use Towa\GebruederWeissSDK\Model\Address;
use Towa\GebruederWeissSDK\Model\AddressReference;
use Towa\GebruederWeissSDK\Model\Contact;
use Towa\GebruederWeissSDK\Model\InlineObject as CreateLogisticsOrderPayload;
use Towa\GebruederWeissSDK\Model\LingualText;
use Towa\GebruederWeissSDK\Model\LogisticsAddress;
use Towa\GebruederWeissSDK\Model\LogisticsOrder;
use Towa\GebruederWeissSDK\Model\LogisticsOrderCallbacks;
use Towa\GebruederWeissSDK\Model\LogisticsProduct;
use Towa\GebruederWeissSDK\Model\LogisticsRequirements;
use Towa\GebruederWeissSDK\Model\OrderLine;
use Towa\GebruederWeissSDK\Model\OrderLineNote;

/**
 * Factory for creating Logistics Orders
 */
class LogisticsOrderFactory
{
    /**
     * Settings Repository
     *
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * Creates an instance of the logistics order factory
     *
     * @param SettingsRepository $settingsRepository An instance of the settings repository.
     */
    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Creates a logistics order from a WooCommerce order
     *
     * @param object $wooCommerceOrder The order to be converted into a logistics order.
     * @return CreateLogisticsOrderPayload
     */
    public function buildFromWooCommerceOrder(object $wooCommerceOrder): CreateLogisticsOrderPayload
    {
        $payload = new CreateLogisticsOrderPayload();

        $logisticsOrder = new LogisticsOrder();
        $logisticsOrder->setCreationDateTime($wooCommerceOrder->get_date_created());
        $logisticsOrder->setCustomerId($this->settingsRepository->getCustomerId());

        $logisticsOrder->setLogisticsAddresses([
            $this->createConsigneeAddress($wooCommerceOrder),
            $this->createOrderbyAddress()
        ]);

        $logisticsOrder->setCustomerOrder(strval($wooCommerceOrder->get_id()));

        $logisticsProduct = new LogisticsProduct();
        $logisticsProduct->setProduct("OUTBOUND_DELIVERY");
        $logisticsProduct->setProductServiceLevel("STANDARD");

        $logisticsRequirements = new LogisticsRequirements();
        $logisticsRequirements->setLogisticsProduct($logisticsProduct);
        $logisticsOrder->setLogisticsRequirements($logisticsRequirements);

        $logisticsOrder->setOrderLines(
            $this->createOrderLines($wooCommerceOrder)
        );

        $payload->setLogisticsOrder($logisticsOrder);

        $callbacks = new LogisticsOrderCallbacks();
        $callbacks->setSuccessCallback($this->settingsRepository->getRestUrl() . "gebrueder-weiss-woocommerce/v1/orders/" . $wooCommerceOrder->get_id() . "/callbacks/success");
        $callbacks->setFulfillmentCallback($this->settingsRepository->getRestUrl() . "gebrueder-weiss-woocommerce/v1/orders/" . $wooCommerceOrder->get_id() . "/callbacks/fulfillment");

        $payload->setCallbacks($callbacks);

        return $payload;
    }

    /**
     * Creates the consignee address from a WooCommerce Order.
     *
     * @param object $wooCommerceOrder The WooCommerce order.
     * @return LogisticsAddress
     */
    private function createConsigneeAddress(object $wooCommerceOrder): LogisticsAddress
    {
        $logisticsAddress = new LogisticsAddress();
        $logisticsAddress->setAddressType("CONSIGNEE");

        $address = new Address();
        $address->setName1($wooCommerceOrder->get_shipping_first_name());
        $address->setName2($wooCommerceOrder->get_shipping_last_name());
        $address->setName3($wooCommerceOrder->get_shipping_company());
        $address->setStreet1($wooCommerceOrder->get_shipping_address_1());
        $address->setStreet2(!empty($wooCommerceOrder->get_shipping_address_2()) ? $wooCommerceOrder->get_shipping_address_2() : null);
        $address->setCity($wooCommerceOrder->get_shipping_city());
        $address->setZipCode($wooCommerceOrder->get_shipping_postcode());
        $address->setCountryCode($wooCommerceOrder->get_shipping_country());
        $address->setState($wooCommerceOrder->get_shipping_state());
        $logisticsAddress->setAddress($address);

        $fullName = implode(" ", array_filter([$wooCommerceOrder->get_shipping_first_name(), $wooCommerceOrder->get_shipping_last_name()]));

        $email = $wooCommerceOrder->get_billing_email();
        $phone = $wooCommerceOrder->get_billing_phone();

        $contact = new Contact();
        $contact->setName($fullName);
        $contact->setEmail(!empty($email) ? $email : null);
        $contact->setPhone(!empty($phone) ? $phone : null);
        $contact->setLanguage("en-US");
        $logisticsAddress->setContact($contact);

        return $logisticsAddress;
    }

    /**
     * Creates the order by address
     *
     * @return LogisticsAddress
     */
    private function createOrderbyAddress(): LogisticsAddress
    {
        $logisticsAddress = new LogisticsAddress();
        $logisticsAddress->setAddressType("ORDERBY");

        $addressReference = new AddressReference();
        $addressReference->setQualifier("CUSTOMER_ID");
        $addressReference->setReference(strval($this->settingsRepository->getCustomerId()));
        $logisticsAddress->setAddressReferences([$addressReference]);

        return $logisticsAddress;
    }

    /**
     * Creates order lines based on a WooCommerce order.
     *
     * @param object $wooCommerceOrder The WooCommerce order.
     * @return array
     */
    private function createOrderLines(object $wooCommerceOrder): array
    {
        return array_map(function (object $orderItem) use ($wooCommerceOrder) {
            $orderLine = new OrderLine();

            $orderLine->setArticleId(strval($orderItem->get_product()->get_sku()));
            $orderLine->setLineItemNumber($orderItem->get_id());
            $orderLine->setQuantity($orderItem->get_quantity());

            $customerNote = new LingualText();
            $customerNote->setLanguage("en-US");
            $customerNote->setText($wooCommerceOrder->get_customer_note());

            $logisticsRequirementNote = new OrderLineNote();
            $logisticsRequirementNote->setNoteType("DELIVERYNOTE");
            $logisticsRequirementNote->setNoteText($customerNote);

            $orderLine->setNotes([$logisticsRequirementNote]);

            return $orderLine;

            /**
             * We need to remove the keys from the items array since they are not in order.
             * Not removing them will cause PHP to serialize the array as an object.
             */
        }, array_values($wooCommerceOrder->get_items("line_item")));
    }
}
