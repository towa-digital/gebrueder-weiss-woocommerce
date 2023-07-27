<?php

use Towa\GebruederWeissWooCommerce\OAuth\OAuthAuthenticator;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mockery\MockInterface;
use Towa\GebruederWeissWooCommerce\Exceptions\AuthenticationFailedException;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;
use Towa\GebruederWeissWooCommerce\SettingsRepository;


uses(\Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration::class);

test('successful authentication', function () {
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

    expect($authenticationToken->getToken())->toEqual('MTQ0NjJkZmQ5OTM2NDE1ZTZjNGZmZjI3');
});

test('failed authentication', function () {
    $this->expectException(AuthenticationFailedException::class);

    /** @var GenericProvider|MockInterface */
    $authProvider = Mockery::mock(GenericProvider::class);
    $authProvider->shouldReceive('getAccessToken')->andThrow(new IdentityProviderException('Invalid parameters.', 500, ''));

    /** @var SettingsRepository|MockInterface */
    $settingsRepository = Mockery::mock(SettingsRepository::class);
    $settingsRepository->allows(['getClientId' => 'client-id', "getClientSecret" => "client-secret"]);

    $authenticator = new OAuthAuthenticator($authProvider, $settingsRepository);

    $authenticator->authenticate();
});

test('it can update the auth token', function () {
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

    /** @var OAuthToken|MockInterface */
    $token = Mockery::mock(OAuthToken::class);
    $token->allows(["isValid" => false]);

    /** @var SettingsRepository|MockInterface */
    $settingsRepository = Mockery::mock(SettingsRepository::class);
    $settingsRepository->allows([
      'getClientId' => 'client-id',
      "getClientSecret" => "client-secret",
      "getAccessToken" => $token,
    ]);
    $settingsRepository->shouldReceive("setAccessToken");

    $authenticator = new OAuthAuthenticator($authProvider, $settingsRepository);

    $authenticator->updateAuthTokenIfNecessary();

    $settingsRepository->shouldHaveReceived("setAccessToken", [OAuthToken::class]);
});

test('it updates the token if no token is available', function () {
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
    $settingsRepository->allows([
      'getClientId' => 'client-id',
      "getClientSecret" => "client-secret",
      "getAccessToken" => null,
    ]);
    $settingsRepository->shouldReceive("setAccessToken");

    $authenticator = new OAuthAuthenticator($authProvider, $settingsRepository);

    $authenticator->updateAuthTokenIfNecessary();

    $settingsRepository->shouldHaveReceived("setAccessToken", [OAuthToken::class]);
});

test('it does not update the auth token if not necessary', function () {
    /** @var GenericProvider|MockInterface */
    $authProvider = Mockery::mock(GenericProvider::class);

    /** @var OAuthToken|MockInterface */
    $token = Mockery::mock(OAuthToken::class);
    $token->allows(["isValid" => true]);

    /** @var SettingsRepository|MockInterface */
    $settingsRepository = Mockery::mock(SettingsRepository::class);
    $settingsRepository->allows([
      "getClientId" => "client-id",
      "getClientSecret" => "client-secret",
      "getAccessToken" => $token,
    ]);

    $authenticator = new OAuthAuthenticator($authProvider, $settingsRepository);

    $authenticator->updateAuthTokenIfNecessary();

    $settingsRepository->shouldNotHaveReceived("setAccessToken", [OAuthToken::class]);
});
