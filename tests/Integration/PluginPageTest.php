<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\Plugin;
use Towa\GebruederWeissWooCommerce\Options\Option;
use Towa\GebruederWeissWooCommerce\Options\OptionPage;


test('if option registration registers settings', function () {
    global $wp_settings_fields;

    $option = (new Option('testname', 'testslug', 'testdescription', 'testgroup', 'string', null));
    $option->registerOption();

    expect($wp_settings_fields)->toHaveKey("gbw-woocommerce");
    expect($wp_settings_fields['gbw-woocommerce'])->toHaveKey("testgroup");

    // all options will be prefixed with 'gbw_'
    expect($wp_settings_fields['gbw-woocommerce']["testgroup"])->toHaveKey("gbw_testslug");

    $setOption = $wp_settings_fields['gbw-woocommerce']["testgroup"]["gbw_testslug"];
    expect($setOption["id"])->toEqual("gbw_testslug");
    expect($setOption["title"])->toEqual("testname");
});

test('if settings can be set', function () {
    $slug = 'customer_id';
    $slug2 = 'client_secret';

    update_option(Option::OPTIONS_PREFIX . $slug, 12345);
    update_option(Option::OPTIONS_PREFIX . $slug2, 'test');

    $option = new Option('client Secret', $slug, 'testdescription', 'testgroup', 'number');
    $option2 = new Option('client Secret', $slug2, 'testdescription', 'testgroup', 'string');

    expect($option->getValue())->toEqual(12345);
    expect($option2->getValue())->toEqual('test');
});

test('if page renders', function () {
    $optionsPage = (new OptionPage('test', Plugin::OPTION_PAGE_SLUG));

    \ob_start();
    $optionsPage->render();
    $html = \ob_get_clean();

    $this->assertStringContainsString('<form method="post" action="options.php"', $html);
    $this->assertStringContainsString('<input type="submit"', $html);
});

test('if option renders', function () {
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
});
