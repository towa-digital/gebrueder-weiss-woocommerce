<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\Options\FulfillmentOptionsTab;

private const NUMBER_OF_STATES = 4;

test('it has the woocommerce order states as options', function () {
    $states = ["order-state-key" => "Order State Name"];
    $fulfillmentTab = new FulfillmentOptionsTab($states);

    expect($fulfillmentTab->options)->toHaveCount(self::NUMBER_OF_STATES);

    for ($i = 0; $i < self::NUMBER_OF_STATES; ++$i) {
        expect($fulfillmentTab->options[$i]->options)->toBe($states);
    }
});
