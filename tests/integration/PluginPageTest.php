<?php

namespace Tests;

use GbWeiss\includes\GbWeiss;
use GbWeiss\includes\Option;
use GbWeiss\includes\OptionPage;

class PluginPageTest extends \WP_UnitTestCase
{
    public function test_if_option_registration_registers_settings()
    {
        global $wp_settings_fields;

        $option = (new Option('testname', 'testslug', 'testdescription', 'testgroup', 'string', null));
        $option->registerOption();

        $this->assertArrayHasKey("gbw-woocommerce", $wp_settings_fields);
        $this->assertArrayHasKey("testgroup", $wp_settings_fields['gbw-woocommerce']);

        // all options will be prefixed with 'gbw_'
        $this->assertArrayHasKey("gbw_testslug", $wp_settings_fields['gbw-woocommerce']["testgroup"]);

        $setOption = $wp_settings_fields['gbw-woocommerce']["testgroup"]["gbw_testslug"];
        $this->assertEquals("gbw_testslug", $setOption["id"]);
        $this->assertEquals("testname", $setOption["title"]);
    }

    public function test_if_settings_can_be_set()
    {
        $slug = 'customer_id';
        $slug2 = 'client_secret';

        update_option(Option::OPTIONS_PREFIX . $slug, 12345);
        update_option(Option::OPTIONS_PREFIX . $slug2, 'test');

        $option = new Option('client Secret', $slug, 'testdescription', 'testgroup', 'number');
        $option2 = new Option('client Secret', $slug2, 'testdescription', 'testgroup', 'string');

        $this->assertEquals(12345, $option->getValue());
        $this->assertEquals('test', $option2->getValue());
    }

    public function test_if_page_renders()
    {
        $optionsPage = (new OptionPage('test', GbWeiss::OPTIONPAGESLUG));

        \ob_start();
        $optionsPage->render();
        $html = \ob_get_clean();

        $this->assertStringContainsString('<form method="post" action="options.php"', $html);
        $this->assertStringContainsString('<input type="submit"', $html);
    }

    public function test_if_option_renders()
    {
        $slug = 'testslug';
        $valueToTest = '12345';

        // set testvalue
        update_option(Option::OPTIONS_PREFIX . $slug, $valueToTest);

        $option = new Option('testname', $slug, 'description', 'testgroup', 'string');

        \ob_start();
        $option->render();
        $html = \ob_get_clean();

        $this->assertStringContainsString('<input type="text"', $html);
        $this->assertStringContainsString('name="' . Option::OPTIONS_PREFIX . $slug . '"', $html);
        $this->assertStringContainsString('value="' . $valueToTest . '"', $html);
    }
}
