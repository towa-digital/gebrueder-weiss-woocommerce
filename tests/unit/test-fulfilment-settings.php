<?php
/**
 * Class SampleTest
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

 namespace Tests;

use GbWeiss\includes\FulfillmentOptionsTab;

/**
 * Fulfillment Settings Test.
 */
class FulfillmentSettingsTest extends \WP_UnitTestCase
{

    /**
     * A single example test.
     */
    public function test_it_has_the_woocommerce_order_states_as_options()
    {
        $states = [ "order-state-key" => "Order State Name" ];
        $fulfillmentTab = new FulfillmentOptionsTab($states);

        $this->assertCount(3, $fulfillmentTab->options);
        $this->assertSame($states, $fulfillmentTab->options[0]->options);
        $this->assertSame($states, $fulfillmentTab->options[1]->options);
        $this->assertSame($states, $fulfillmentTab->options[2]->options);
    }
}
