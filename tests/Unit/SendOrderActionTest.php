<?php

namespace Tests\Unit;

use Towa\GebruederWeissWooCommerce\Actions\SendOrderAction;
use PHPUnit\Framework\TestCase;
use Towa\GebruederWeissWooCommerce\SettingsRepository;

class SendOrderActionTest extends TestCase
{
    /** @doesNotPerformAssertions */
    public function test_it_sets_the_order_state_to_the_fullfillment_state()
    {
        $fulfillmentState = "test";
        $actionNote = "GBW Fulfillment triggered via action";

        $order = \Mockery::mock('WC_Order');
        $order->allows([
            "set_status" => null,
            "save" => null,
        ]);
        $settingsRepository = \Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows([
            "getFulfillmentState" => $fulfillmentState,
        ]);

        $sendOrderAction = new SendOrderAction($settingsRepository);
        $sendOrderAction->sendOrderToGbw($order);

        $order->shouldHaveReceived('set_status')->with(
            $fulfillmentState,
            $actionNote,
            true
        );
    }

    public function test_it_adds_the_send_to_gbw_action_to_the_order_action()
    {
        $sendOrderAction = new SendOrderAction(\Mockery::mock(SettingsRepository::class));
        $actions = $sendOrderAction->addSendToGbwActionToOrderAction([]);

        $this->assertArrayHasKey(SendOrderAction::ACTION_KEY, $actions);
    }
}
