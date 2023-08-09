<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\Support\Transient;

class TransientTest extends \WP_UnitTestCase
{
    public function test_it_can_delete_transients()
    {
        set_transient('test', 'test', 60);
        Transient::deleteTransient("test");
        $test = get_transient('test');
        $this->assertFalse($test);
    }

    public function test_it_sets_transients_automatically_with_callback()
    {
        $transientKey = 'test_key';
        $transientValue = 'test_value';

        $callback = function ($test) {
            return $test;
        };

        $initialReturnValue = Transient::getTransient($transientKey, $callback, $transientValue, 60);

        $savedValue = get_transient($transientKey);

        $this->assertSame($transientValue, $savedValue);
        $this->assertSame($transientValue, $initialReturnValue);
    }
}
