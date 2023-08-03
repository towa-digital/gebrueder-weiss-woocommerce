<?php
/**
 * Sender Order Action
 *
 * @package Plugin
 */

namespace Towa\GebruederWeissWooCommerce\Actions;

use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\SettingsRepository;

/**
 * Sender Order Action
 */
class SendOrderAction
{
    const ACTION_KEY = "send_to_gbw";

    /**
     * Settings Repository
     *
     * @var SettingsRepository
     */
    private $settingsRepository = null;

    /**
     * Constructor
     *
     * @param SettingsRepository $settingsRepository An instance of the settings repository.
     */
    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Sets the OrderStatus to the fulfillment state.
     * This will trigger the order to be sent to GBW.
     *
     * @param object $order The order (WC_Order) to be sent to GBW.
     */
    public function sendOrderToGbw(object $order)
    {
        $fulfillmentState = $this->settingsRepository->getFulfillmentState();

        $order->set_status(
            $fulfillmentState,
            __('GBW Fulfillment triggered via action', Plugin::LANGUAGE_DOMAIN),
            true
        );
        $order->save();
    }

    /**
     * Adds Callbacks to woocommerce order Actions.
     */
    public function addActions()
    {
        \add_action('woocommerce_order_actions', [$this, 'addSendToGbwActionToOrderAction']);
        \add_action('woocommerce_order_action_' . self::ACTION_KEY, [$this, 'sendOrderToGbw']);
    }

    /**
     * Adds the "send to GBW" action to the order actions.
     *
     * @param array $actions The order actions.
     */
    public function addSendToGbwActionToOrderAction(array $actions): array
    {
        global $theorder;

        $actions[self::ACTION_KEY] = __('Set status to fulfillment state, to send to Gebrüder Weiss', Plugin::LANGUAGE_DOMAIN);

        return $actions;
    }
}
