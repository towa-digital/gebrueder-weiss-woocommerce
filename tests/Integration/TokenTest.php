<?php

namespace Tests\Integration;

use GbWeiss\includes\GbWeiss;
use GbWeiss\includes\OAuth\OAuthAuthenticator;
use GbWeiss\includes\OAuth\OAuthToken;
use Mockery;

class TokenTest extends \WP_UnitTestCase
{
    /**
     * Test if an Access Token can be retrieved and stored in the options table.
     */
    public function test_retrieve_and_store_token()
    {
        $token = new OAuthToken('testToken', 'Bearer', 3600, '');
        $mock = Mockery::mock(OAuthAuthenticator::class);
        $mock->shouldReceive('authenticate')
          ->andReturn($token);
        $plugin = GbWeiss::getInstance();
        $plugin->setAuthenticationClient($mock);
        $plugin->updateAuthToken();
        $this->assertEquals($token->getAccessToken(), $plugin->getAccessToken());
    }
}
