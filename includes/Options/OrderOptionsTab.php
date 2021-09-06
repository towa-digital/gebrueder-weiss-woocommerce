<?php
/**
 * Order Options Tab
 *
 * @package Options
 */

namespace Towa\GebruederWeissWooCommerce\Options;

defined('ABSPATH') || exit;

use Towa\GebruederWeissWooCommerce\Plugin;

/**
 * Order Options Tab
 */
class OrderOptionsTab extends Tab
{
    const TAB_SLUG = 'order';
    const ORDER_ID_FIELD_NAME = 'order_id_field';
    const ORDER_ID_FIELD_DEFAULT_VALUE = "gbw_order_id";
    const TRACKING_LINK_FIELD_NAME = 'tracking_link_field';
    const TRACKING_LINK_FIELD_DEFAULT_VALUE = "gbw_tracking_link";
    const CARRIER_INFORMATION_FIELD_NAME = 'carrier_information_field';
    const CARRIER_INFORMATION_FIELD_DEFAULT_VALUE = "gbw_carrier_information";

    /**
     * Creates the order options tab
     *
     * @param array $orderCustomFields Custom fields available for orders.
     */
    public function __construct($orderCustomFields)
    {
        parent::__construct(__('Order', Plugin::LANGUAGE_DOMAIN), self::TAB_SLUG);

        $options = $this->createOptionsFromFieldKeys($orderCustomFields);
        $orderIdOptions = $this->addDefaultValueToOptions($options, self::ORDER_ID_FIELD_DEFAULT_VALUE);
        $trackingLinkOptions = $this->addDefaultValueToOptions($options, self::TRACKING_LINK_FIELD_DEFAULT_VALUE);
        $carrierInformationOptions = $this->addDefaultValueToOptions($options, self::CARRIER_INFORMATION_FIELD_DEFAULT_VALUE);

        $this
            ->addOption(new OptionDropdown('Order Id Field', self::ORDER_ID_FIELD_NAME, __('Order Id Field', Plugin::LANGUAGE_DOMAIN), self::TAB_SLUG, $orderIdOptions))
            ->addOption(new OptionDropdown('Tracking Link Field', self::TRACKING_LINK_FIELD_NAME, __('Tracking Link Field', Plugin::LANGUAGE_DOMAIN), self::TAB_SLUG, $trackingLinkOptions))
            ->addOption(new OptionDropdown('Carrier Information Field', self::CARRIER_INFORMATION_FIELD_NAME, __('Carrier Information Field', Plugin::LANGUAGE_DOMAIN), self::TAB_SLUG, $carrierInformationOptions));
    }

    /**
     * Creates options from field keys
     *
     * @param array $orderCustomFields Custom fields available for orders.
     * @return array
     */
    private function createOptionsFromFieldKeys(array $orderCustomFields): array
    {
        $options = [];
        foreach ($orderCustomFields as $orderCustomField) {
            $options[$orderCustomField] = $orderCustomField;
        }

        return $options;
    }

    /**
     * Adds a default value to the options
     *
     * @param array  $options Options to add the default value to.
     * @param string $defaultValue Default value to add.
     * @return array
     */
    private function addDefaultValueToOptions(array $options, string $defaultValue): array
    {
        if (array_key_exists($defaultValue, $options)) {
            return $options;
        }

        $options[$defaultValue] = $defaultValue;

        return $options;
    }
}
