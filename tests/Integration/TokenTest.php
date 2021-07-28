<?php

namespace Tests\Integration;

use GbWeiss\includes\GbWeiss;
use GbWeiss\includes\OAuth\OAuthAuthenticator;
use GbWeiss\includes\OAuth\OAuthToken;
use GbWeiss\includes\SettingsRepository;
use Mockery;
use Mockery\MockInterface;

class TokenTest extends \WP_UnitTestCase
{
    /**
     * Test if an Access Token can be retrieved and stored in the options table.
     */
    public function test_retrieve_and_store_token()
    {
        $token = new OAuthToken('testToken', 'Bearer', 3600, '');

        /** @var OAuthAuthenticator|MockInterface */
        $authenticationClient = Mockery::mock(OAuthAuthenticator::class);
        $authenticationClient->shouldReceive('authenticate')->andReturn($token);

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->shouldReceive("getClientId")->andReturn("id");
        $settingsRepository->shouldReceive("getClientSecret")->andReturn("secret");
        $settingsRepository->shouldReceive("setAccessToken");

        /** @var GbWeiss */
        $plugin = GbWeiss::getInstance();
        $plugin->setAuthenticationClient($authenticationClient);
        $plugin->setSettingsRepository($settingsRepository);

        $plugin->updateAuthToken();

        $this->assertEquals('testToken', $token->getAccessToken());
    }
}
