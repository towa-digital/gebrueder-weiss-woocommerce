<?php
/**
 * Fulfillment Options Tab
 *
 * @package Options
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

/**
 * Fulfillment Options Tab
 */
class FulfillmentOptionsTab extends Tab
{

    /**
     * Creates the fulfillment options based on the passed states.
     *
     * @param array $orderStates The states to be shown as options in the dropdowns.
     */
    public function __construct(array $orderStates)
    {
        parent::__construct(__('Fulfillment', GbWeiss::$languageDomain), 'fulfillment');
        $this
            ->addOption(new OptionDropdown('Fulfillment State', 'fulfillmentState', __('Fulfillment State', GbWeiss::$languageDomain), 'fulfillment', $orderStates))
            ->addOption(new OptionDropdown('Fulfilled State', 'fulfilledState', __('Fulfilled State', GbWeiss::$languageDomain), 'fulfillment', $orderStates))
            ->addOption(new OptionDropdown('Fulfillment Error State', 'fulfillmentErrorState', __('Fulfillment Error State', GbWeiss::$languageDomain), 'fulfillment', $orderStates));
    }
}
