<?php
/**
 * Class SampleTest
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

 namespace Tests;

use GbWeiss\includes\FulfilmentOptionsTab;
use GbWeiss\includes\GbWeiss;
use GbWeiss\includes\OrderStateRepository;

/**
 * Fulfilment Settings Test.
 */
class FulfilmentSettingsTest extends \WP_UnitTestCase
{

    /**
     * A single example test.
     */
    public function test_it_has_the_woocommerce_order_states_as_options()
    {
        $statuses = [ "order-state-key" => "Order State Name" ];
        $fulfilmentTab = new FulfilmentOptionsTab($statuses);

        $this->assertCount(3, $fulfilmentTab->options);
        $this->assertSame($statuses, $fulfilmentTab->options[0]->options);
        $this->assertSame($statuses, $fulfilmentTab->options[1]->options);
        $this->assertSame($statuses, $fulfilmentTab->options[2]->options);
    }
}
