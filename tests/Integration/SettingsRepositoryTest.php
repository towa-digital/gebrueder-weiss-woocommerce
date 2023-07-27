<?php

uses(\WP_UnitTestCase::class);
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;
use Towa\GebruederWeissWooCommerce\Options\OrderOptionsTab;
use Towa\GebruederWeissWooCommerce\SettingsRepository;


test('it can retrieve the client id', function () {
    update_option("gbw_client_id", "test");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getClientId())->toBe("test");
});

test('it can retrieve the client secret', function () {
    update_option("gbw_client_secret", "test");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getClientSecret())->toBe("test");
});

test('it can retrieve the access token', function () {
    $token = new OAuthToken("test", time());
    update_option("gbw_accessToken", $token);

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getAccessToken()->getToken())->toBe($token->getToken());
});

test('it returns null if deserializing the token fails', function () {
    update_option("gbw_accessToken", "not-a-serialized-token");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getAccessToken())->toBeNull();
});

test('it returns null if the deserialized class is not a token', function () {
    update_option("gbw_accessToken", serialize(["not", "a", "token"]));

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getAccessToken())->toBeNull();
});

test('it can retrieve the fulfillment state', function () {
    update_option("gbw_fulfillmentState", "test");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getFulfillmentState())->toBe("test");
});

test('it can retrieve the fulfilled state', function () {
    update_option("gbw_fulfilledState", "test");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getFulfilledState())->toBe("test");
});

test('it can retrieve the fulfillment error state', function () {
    update_option("gbw_fulfillmentErrorState", "test");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getFulfillmentErrorState())->toBe("test");
});

test('it can set the auth token', function () {
    $settingsRepository = new SettingsRepository();

    $token = new OAuthToken("token", time());
    $settingsRepository->setAccessToken($token);

    expect(get_option("gbw_accessToken"))->toBe(serialize($token));
});

test('it can retrieve the site url', function () {
    $settingsRepository = new SettingsRepository();

    $siteUrl = "http://test.com";
    update_option('siteurl', $siteUrl);

    expect($settingsRepository->getSiteUrl())->toBe($siteUrl);
});

test('it can retrieve the rest url', function () {
    $settingsRepository = new SettingsRepository();

    $homeUrl = "http://test.com";
    $restUrlToTest = $homeUrl . "/wp-json/";
    update_option('home', $homeUrl);
    update_option('permalink_structure', '/%postname%/');

    expect($settingsRepository->getRestUrl())->toBe($restUrlToTest);
});

test('it can retrieve the customer id', function () {
    update_option("gbw_customer_id", 42);

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getCustomerId())->toBe(42);
});

test('it can retrieve the order id field', function () {
    update_option("gbw_order_id_field", "asdf");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getOrderIdFieldName())->toBe("asdf");
});

test('it retrieves a default order id field name', function () {
    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getOrderIdFieldName())->toBe(OrderOptionsTab::ORDER_ID_FIELD_DEFAULT_VALUE);
});

test('it can retrieve the tracking link field', function () {
    update_option("gbw_tracking_link_field", "tracking_link");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getTrackingLinkFieldName())->toBe("tracking_link");
});

test('it retrieves a default tracking link field name', function () {
    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getTrackingLinkFieldName())->toBe(OrderOptionsTab::TRACKING_LINK_FIELD_DEFAULT_VALUE);
});

test('it can retrieve the carrier information', function () {
    update_option("gbw_carrier_information_field", "carrier_information");

    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getCarrierInformationFieldName())->toBe("carrier_information");
});

test('it retrieves a default carrier information field name', function () {
    $settingsRepository = new SettingsRepository();

    expect($settingsRepository->getCarrierInformationFieldName())->toBe(OrderOptionsTab::CARRIER_INFORMATION_FIELD_DEFAULT_VALUE);
});
