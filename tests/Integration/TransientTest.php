<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\Support\Transient;


test('it can delete transients', function () {
    set_transient('test', 'test', 60);
    Transient::deleteTransient("test");
    $test = get_transient('test');
    expect($test)->toBeFalse();
});

test('it sets transients automatically with callback', function () {
    $transientKey = 'test_key';
    $transientValue = 'test_value';

    $callback = function ($test) {
        return $test;
    };

    $initialReturnValue = Transient::getTransient($transientKey, $callback, $transientValue, 60);

    $savedValue = get_transient($transientKey);

    expect($savedValue)->toBe($transientValue);
    expect($initialReturnValue)->toBe($transientValue);
});
