<?php

namespace Tests\Integration;

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
        update_option("gbw_accessToken", "test");

        $settingsRepository = new SettingsRepository();

        $this->assertSame("test", $settingsRepository->getAccessToken());
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

        $token = "token";
        $settingsRepository->setAccessToken($token);

        $this->assertSame($token, get_option("gbw_accessToken"));
    }
}
