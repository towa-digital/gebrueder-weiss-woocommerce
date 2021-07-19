<?php
/**
 * Fulfilment Options Tab
 *
 * @package GbWeissOptions
 */

namespace GbWeiss\includes;

/**
 * Fulfilment Options Tab
 */
class FulfilmentOptionsTab extends Tab
{

    /**
     * Creates the fulfilment options based on the passed statuses.
     *
     * @param array $orderStatuses The statuses to be shown as options in the dropdowns.
     */
    public function __construct(array $orderStatuses)
    {
        parent::__construct(__('Fulfilment', GbWeiss::$languageDomain), 'fulfilment');
        $this
            ->addOption(new OptionDropdown('Fulfilment State', 'fulfilmentState', __('Fulfilment State', GbWeiss::$languageDomain), 'fulfilment', $orderStatuses))
            ->addOption(new OptionDropdown('Fulfilled State', 'fulfilledState', __('Fulfilled State', GbWeiss::$languageDomain), 'fulfilment', $orderStatuses))
            ->addOption(new OptionDropdown('Fulfilment Error State', 'fulfilmentErrorState', __('Fulfilment Error State', GbWeiss::$languageDomain), 'fulfilment', $orderStatuses));
    }
}
