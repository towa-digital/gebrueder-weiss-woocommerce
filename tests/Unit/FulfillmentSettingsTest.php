<?php

namespace Tests\Unit;

use Towa\GebruederWeissWooCommerce\Options\FulfillmentOptionsTab;
use WP_UnitTestCase;

class FulfillmentSettingsTest extends WP_UnitTestCase
{
    private const NUMBER_OF_STATES = 4;

    public function test_it_has_the_woocommerce_order_states_as_options()
    {
        $states = ["order-state-key" => "Order State Name"];
        $fulfillmentTab = new FulfillmentOptionsTab($states);

        $this->assertCount(self::NUMBER_OF_STATES, $fulfillmentTab->options);

        for ($i = 0; $i < self::NUMBER_OF_STATES; ++$i) {
            $this->assertSame($states, $fulfillmentTab->options[$i]->options);
        }
    }
}
