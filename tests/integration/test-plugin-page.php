
<?php

use GbWeiss\includes\GbWeiss;
use GbWeiss\includes\Option;

class TestPluginPage extends \WP_UnitTestCase
{
  public function setUp(): void
  {
    parent::setUp();
    // reset global settings between tests
    global $wp_settings_fields;
    $wp_settings_fields = null;
  }

  /** @test */
  public function test_if_option_registration_registers_settings()
  {
    global $wp_settings_fields;

    $option = (new Option('testname','testslug','testdescription', 'testgroup','string', null));
    $option->registerOption();

    $this->assertArrayHasKey("gbw-woocommerce", $wp_settings_fields);
    $this->assertArrayHasKey("testgroup", $wp_settings_fields['gbw-woocommerce']);

    // all options will be prefixed with 'gbw_'
    $this->assertArrayHasKey("gbw_testslug", $wp_settings_fields['gbw-woocommerce']["testgroup"]);

    $setOption = $wp_settings_fields['gbw-woocommerce']["testgroup"]["gbw_testslug"];
    $this->assertEquals("gbw_testslug", $setOption["id"]);
    $this->assertEquals("testname", $setOption["title"]);
  }

}