<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;
use Towa\GebruederWeissWooCommerce\Options\OrderOptionsTab;
use Towa\GebruederWeissWooCommerce\SettingsRepository;

class SettingsRepositoryTest extends \WP_UnitTestCase
{
    public function test_it_can_retrieve_the_client_id()
    {
        update_option("gbw_client_id", "test");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("test", $settingsRepository->getClientId());
    }

    public function test_it_can_retrieve_the_client_secret()
    {
        update_option("gbw_client_secret", "test");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("test", $settingsRepository->getClientSecret());
    }

    public function test_it_can_retrieve_the_access_token()
    {
        $token = new OAuthToken("test", time());
        update_option("gbw_accessToken", $token);

        $settingsRepository = new SettingsRepository();

        $this->assertSame($token->getToken(), $settingsRepository->getAccessToken()->getToken());
    }

    public function test_it_returns_null_if_deserializing_the_token_fails()
    {
        update_option("gbw_accessToken", "not-a-serialized-token");

        $settingsRepository = new SettingsRepository();

        $this->assertNull($settingsRepository->getAccessToken());
    }

    public function test_it_returns_null_if_the_deserialized_class_is_not_a_token()
    {
        update_option("gbw_accessToken", serialize(["not", "a", "token"]));

        $settingsRepository = new SettingsRepository();

        $this->assertNull($settingsRepository->getAccessToken());
    }

    public function test_it_can_retrieve_the_fulfillment_state()
    {
        update_option("gbw_fulfillmentState", "test");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("test", $settingsRepository->getFulfillmentState());
    }

    public function test_it_can_retrieve_the_fulfilled_state()
    {
        update_option("gbw_fulfilledState", "test");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("test", $settingsRepository->getFulfilledState());
    }

    public function test_it_can_retrieve_the_fulfillment_error_state()
    {
        update_option("gbw_fulfillmentErrorState", "test");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("test", $settingsRepository->getFulfillmentErrorState());
    }

    public function test_it_can_set_the_auth_token()
    {
        $settingsRepository = new SettingsRepository();

        $token = new OAuthToken("token", time());
        $settingsRepository->setAccessToken($token);

        $this->assertSame(serialize($token), get_option("gbw_accessToken"));
    }

    public function test_it_can_retrieve_the_site_url()
    {
        $settingsRepository = new SettingsRepository();

        $siteUrl = "http://test.com";
        update_option('siteurl', $siteUrl);

        $this->assertSame($siteUrl, $settingsRepository->getSiteUrl());
    }

    public function test_it_can_retrieve_the_rest_url()
    {
        $settingsRepository = new SettingsRepository();

        $homeUrl = "http://test.com";
        $restUrlToTest = $homeUrl . "/wp-json/";
        update_option('home', $homeUrl);
        update_option('permalink_structure', '/%postname%/');

        $this->assertSame($restUrlToTest, $settingsRepository->getRestUrl());
    }

    public function test_it_can_retrieve_the_customer_id()
    {
        update_option("gbw_customer_id", 42);

        $settingsRepository = new SettingsRepository();

        $this->assertSame(42, $settingsRepository->getCustomerId());
    }

    public function test_it_can_retrieve_the_order_id_field()
    {
        update_option("gbw_order_id_field", "asdf");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("asdf", $settingsRepository->getOrderIdFieldName());
    }

    public function test_it_retrieves_a_default_order_id_field_name()
    {
        $settingsRepository = new SettingsRepository();

        $this->assertSame(OrderOptionsTab::ORDER_ID_FIELD_DEFAULT_VALUE, $settingsRepository->getOrderIdFieldName());
    }

    public function test_it_can_retrieve_the_tracking_link_field()
    {
        update_option("gbw_tracking_link_field", "tracking_link");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("tracking_link", $settingsRepository->getTrackingLinkFieldName());
    }

    public function test_it_retrieves_a_default_tracking_link_field_name()
    {
        $settingsRepository = new SettingsRepository();

        $this->assertSame(OrderOptionsTab::TRACKING_LINK_FIELD_DEFAULT_VALUE, $settingsRepository->getTrackingLinkFieldName());
    }

    public function test_it_can_retrieve_the_carrier_information()
    {
        update_option("gbw_carrier_information_field", "carrier_information");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("carrier_information", $settingsRepository->getCarrierInformationFieldName());
    }

    public function test_it_retrieves_a_default_carrier_information_field_name()
    {
        $settingsRepository = new SettingsRepository();

        $this->assertSame(OrderOptionsTab::CARRIER_INFORMATION_FIELD_DEFAULT_VALUE, $settingsRepository->getCarrierInformationFieldName());
    }

    public function test_it_can_retrieve_the_use_gbw_shipping_zones_option()
    {
        update_option("gbw_useGbwShippingZones", true);

        $settingsRepository = new SettingsRepository();

        $this->assertTrue($settingsRepository->getUseGbwShippingZones());
    }
}
