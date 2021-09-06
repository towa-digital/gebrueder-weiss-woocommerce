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
 * Fulfillment Options Tab
 */
class FulfillmentOptionsTab extends Tab
{

    /**
     * Creates the order options tab
     */
    public function __construct()
    {
        parent::__construct(__('Order', Plugin::LANGUAGE_DOMAIN), 'fulfillment');
        $this
            ->addOption(new OptionDropdown('Fulfillment State', 'fulfillmentState', __('Fulfillment State', Plugin::LANGUAGE_DOMAIN), 'fulfillment', $orderStates))
            ->addOption(new OptionDropdown('Fulfilled State', 'fulfilledState', __('Fulfilled State', Plugin::LANGUAGE_DOMAIN), 'fulfillment', $orderStates))
            ->addOption(new OptionDropdown('Fulfillment Error State', 'fulfillmentErrorState', __('Fulfillment Error State', Plugin::LANGUAGE_DOMAIN), 'fulfillment', $orderStates));
    }
}
