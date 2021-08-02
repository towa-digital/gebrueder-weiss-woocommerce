<?php
/**
 * Fulfillment Options Tab
 *
 * @package Options
 */

namespace Towa\GebruederWeissWooCommerce\Options;

defined('ABSPATH') || exit;

use Towa\GebruederWeissWooCommerce\Plugin;

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
        parent::__construct(__('Fulfillment', Plugin::$languageDomain), 'fulfillment');
        $this
            ->addOption(new OptionDropdown('Fulfillment State', 'fulfillmentState', __('Fulfillment State', Plugin::$languageDomain), 'fulfillment', $orderStates))
            ->addOption(new OptionDropdown('Fulfilled State', 'fulfilledState', __('Fulfilled State', Plugin::$languageDomain), 'fulfillment', $orderStates))
            ->addOption(new OptionDropdown('Fulfillment Error State', 'fulfillmentErrorState', __('Fulfillment Error State', Plugin::$languageDomain), 'fulfillment', $orderStates));
    }
}
