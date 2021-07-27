<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use GbWeiss\includes\OAuth\OAuthAuthenticator;
use Mockery;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Sample test case.
 */
class OAuthAuthenticatorTest extends TestCase
{
    /**
     * Test if the client can authenticate against OAuthAuthentication
     */
    public function test_successful_authentication()
    {
        $mock = Mockery::mock(GenericProvider::class);
        $mock->shouldReceive('getAccessToken')
          ->times(1)
          ->andReturn(
              new AccessToken([
                'access_token' => 'MTQ0NjJkZmQ5OTM2NDE1ZTZjNGZmZjI3',
                'refresh_token' => 'testRefreshToken',
                'expires_in' => 3600,
                'resource_owner_id' => '1',
              ])
          );
        $authenticator = new OAuthAuthenticator($mock);
        $authenticationToken = $authenticator->authenticate('1234', '4567');
        $this->assertEquals('MTQ0NjJkZmQ5OTM2NDE1ZTZjNGZmZjI3', $authenticationToken->getAccessToken());
    }

    /**
     * Test if the client can handle authentication failures
     */
    public function test_failed_authentication()
    {
        $mock = Mockery::mock(GenericProvider::class);
        $mock->shouldReceive('getAccessToken')
          ->andThrow(new IdentityProviderException('Invalid parameters.', 500, ''));
        $authenticator = new OAuthAuthenticator($mock);
        try {
            $authenticator->authenticate('1234', '4567');
            $this->assertEquals(true, false);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Authentication failed', $e->getMessage());
        }
    }
}
