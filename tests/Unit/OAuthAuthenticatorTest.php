<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthAuthenticator;
use Mockery;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;
use Towa\GebruederWeissWooCommerce\SettingsRepository;

/**
 * Sample test case.
 */
class OAuthAuthenticatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_successful_authentication()
    {
        /** @var GenericProvider|MockInterface */
        $authProvider = Mockery::mock(GenericProvider::class);
        $authProvider->shouldReceive('getAccessToken')
          ->times(1)
          ->andReturn(
              new AccessToken([
                'access_token' => 'MTQ0NjJkZmQ5OTM2NDE1ZTZjNGZmZjI3',
                'refresh_token' => 'testRefreshToken',
                'expires_in' => 3600,
                'resource_owner_id' => '1',
              ])
          );

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows(['getClientId' => 'client-id', "getClientSecret" => "client-secret"]);

        $authenticator = new OAuthAuthenticator($authProvider, $settingsRepository);

        $authenticationToken = $authenticator->authenticate();

        $this->assertEquals('MTQ0NjJkZmQ5OTM2NDE1ZTZjNGZmZjI3', $authenticationToken->getAccessToken());
    }

    public function test_failed_authentication()
    {
        /** @var GenericProvider|MockInterface */
        $authProvider = Mockery::mock(GenericProvider::class);
        $authProvider->shouldReceive('getAccessToken')->andThrow(new IdentityProviderException('Invalid parameters.', 500, ''));

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows(['getClientId' => 'client-id', "getClientSecret" => "client-secret"]);

        $authenticator = new OAuthAuthenticator($authProvider, $settingsRepository);

        try {
            $authenticator->authenticate();
            $this->assertEquals(true, false);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Authentication failed', $e->getMessage());
        }
    }

    public function test_it_can_update_the_auth_token()
    {
        /** @var GenericProvider|MockInterface */
        $authProvider = Mockery::mock(GenericProvider::class);
        $authProvider->shouldReceive('getAccessToken')
          ->times(1)
          ->andReturn(
              new AccessToken([
                'access_token' => 'MTQ0NjJkZmQ5OTM2NDE1ZTZjNGZmZjI3',
                'refresh_token' => 'testRefreshToken',
                'expires_in' => 3600,
                'resource_owner_id' => '1',
              ])
          );

        /** @var SettingsRepository|MockInterface */
        $settingsRepository = Mockery::mock(SettingsRepository::class);
        $settingsRepository->allows(['getClientId' => 'client-id', "getClientSecret" => "client-secret"]);
        $settingsRepository->shouldReceive("setAccessToken");

        $authenticator = new OAuthAuthenticator($authProvider, $settingsRepository);

        $authenticator->updateAuthToken();

        $settingsRepository->shouldHaveReceived("setAccessToken", [OAuthToken::class]);
    }
}
