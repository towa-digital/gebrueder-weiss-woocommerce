<?php
/**
 * Factory for creating Logistics Orders
 *
 * @package GbWeiss
 */

namespace GbWeiss\includes;

defined('ABSPATH') || exit;

use Towa\GebruederWeissSDK\Model\Address;
use Towa\GebruederWeissSDK\Model\AddressReference;
use Towa\GebruederWeissSDK\Model\Article;
use Towa\GebruederWeissSDK\Model\ArticleNote;
use Towa\GebruederWeissSDK\Model\Contact;
use Towa\GebruederWeissSDK\Model\LingualText;
use Towa\GebruederWeissSDK\Model\LogisticsAddress;
use Towa\GebruederWeissSDK\Model\LogisticsOrder;
use Towa\GebruederWeissSDK\Model\LogisticsRequirements;
use Towa\GebruederWeissSDK\Model\OrderLine;

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
     * @return LogisticsOrder
     */
    public function buildFromWooCommerceOrder(object $wooCommerceOrder): LogisticsOrder
    {
        $logisticsOrder = new LogisticsOrder();
        $logisticsOrder->setUrl($this->settingsRepository->getSiteUrl() . "/wp-json/gebrueder-weiss-woocommerce/v1/update/" . $wooCommerceOrder->get_id());
        $logisticsOrder->setCreationDateTime($wooCommerceOrder->get_date_created());
        $logisticsOrder->setOwnerId($this->settingsRepository->getCustomerId());

        $logisticsOrder->setLogisticsAddresses([
            $this->createConsigneeAddress($wooCommerceOrder),
            $this->createOrderbyAddress()
        ]);

        $logisticsOrder->setOrderLines(
            $this->createOrderLines($wooCommerceOrder)
        );

        return $logisticsOrder;
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
        $logisticsAddress->setAddressType("consignee");

        $address = new Address();
        $address->setName1($wooCommerceOrder->get_shipping_first_name());
        $address->setName2($wooCommerceOrder->get_shipping_last_name());
        $address->setName3($wooCommerceOrder->get_shipping_company());
        $address->setStreet1($wooCommerceOrder->get_shipping_address_1());
        $address->setStreet2($wooCommerceOrder->get_shipping_address_2());
        $address->setCity($wooCommerceOrder->get_shipping_city());
        $address->setZipCode($wooCommerceOrder->get_shipping_postcode());
        $address->setCountryCode($wooCommerceOrder->get_shipping_country());
        $address->setState($wooCommerceOrder->get_shipping_state());
        $logisticsAddress->setAddress($address);

        $fullName = implode(" ", array_filter([$wooCommerceOrder->get_shipping_first_name(), $wooCommerceOrder->get_shipping_last_name()]));

        $contact = new Contact();
        $contact->setName($fullName);
        $contact->setEmail($wooCommerceOrder->get_billing_email());
        $contact->setPhone($wooCommerceOrder->get_billing_phone());
        $contact->setLanguage("de-DE");
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
        $logisticsAddress->setAddressType("orderby");

        $addressReference = new AddressReference();
        $addressReference->setQualifier("gwcustomerid");
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

            $article = new Article();
            $article->setLineItemNumber($orderItem->get_id());
            $article->setArticleId(intval($orderItem->get_product()->get_sku()));
            $article->setQuantity($orderItem->get_quantity());

            $customerNote = new LingualText();
            $customerNote->setLanguage("de-DE");
            $customerNote->setText($wooCommerceOrder->get_customer_note());
            $logisticsRequirementNote = new ArticleNote();
            $logisticsRequirementNote->setNoteType("deliverynotearticle");
            $logisticsRequirementNote->setNoteText($customerNote);

            $logisticsRequirement = new LogisticsRequirements();
            $logisticsRequirement->setNotes([$logisticsRequirementNote]);

            $article->setLogisticsrequirement($logisticsRequirement);

            $orderLine->setArticles([$article]);

            return $orderLine;

        /**
         * We need to remove the keys from the items array since they are not in order.
         * Not removing them will cause PHP to serialize the array as an object.
         */
        }, array_values($wooCommerceOrder->get_items("line_item")));
    }
}
