<?php
/**
 * Fulfillment Options Tab
 *
 * @package GbWeissOptions
 */

namespace GbWeiss\includes;

defined('ABSPATH') || exit;

/**
 * Fulfillment Options Tab
 */
class FulfillmentOptionsTab extends Tab
{

    /**
     * Creates the fulfillment options based on the passed statuses.
     *
     * @param array $orderStatuses The statuses to be shown as options in the dropdowns.
     */
    public function __construct(array $orderStatuses)
    {
        parent::__construct(__('Fulfillment', GbWeiss::$languageDomain), 'fulfillment');
        $this
            ->addOption(new OptionDropdown('Fulfillment State', 'fulfillmentState', __('Fulfillment State', GbWeiss::$languageDomain), 'fulfillment', $orderStatuses))
            ->addOption(new OptionDropdown('Fulfilled State', 'fulfilledState', __('Fulfilled State', GbWeiss::$languageDomain), 'fulfillment', $orderStatuses))
            ->addOption(new OptionDropdown('Fulfillment Error State', 'fulfillmentErrorState', __('Fulfillment Error State', GbWeiss::$languageDomain), 'fulfillment', $orderStatuses));
    }
}
