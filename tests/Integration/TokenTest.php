<?php

namespace Tests\Integration;

use Towa\GebruederWeissWooCommerce\GbWeiss;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthAuthenticator;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;
use Towa\GebruederWeissWooCommerce\SettingsRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;

class TokenTest extends \WP_UnitTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Test if an Access Token can be retrieved and stored in the options table.
     */
    public function test_retrieve_and_store_token()
    {
        $token = new OAuthToken('testToken', 'Bearer', 3600, '');

        /** @var OAuthAuthenticator|MockInterface */
        $authenticationClient = Mockery::mock(OAuthAuthenticator::class);
        $authenticationClient->shouldReceive('authenticate')->once()->andReturn($token);

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->shouldReceive("getClientId")->andReturn("id");
        $settingsRepository->shouldReceive("getClientSecret")->andReturn("secret");
        $settingsRepository->shouldReceive("setAccessToken")->once()->withArgs([$token->getAccessToken()]);

        /** @var GbWeiss */
        $plugin = GbWeiss::getInstance();
        $plugin->setAuthenticationClient($authenticationClient);
        $plugin->setSettingsRepository($settingsRepository);

        $plugin->updateAuthToken();

        $this->assertEquals('testToken', $token->getAccessToken());
    }
}
