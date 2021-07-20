<?php
/**
 * Class OAuthAuthenticatorTest
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use GbWeiss\includes\OAuthAuthenticator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

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
        // Create a mock and queue two responses.
        $mock = new MockHandler([
          new Response(
              200,
              [],
              json_encode([
                "access_token" => "MTQ0NjJkZmQ5OTM2NDE1ZTZjNGZmZjI3",
                "token_type" => "bearer",
                "expires_in" => 3600,
                "refresh_token" => "IwOGYzYTlmM2YxOTQ5MGE3YmNmMDFkNTVk",
                "scope" => "create"
              ])
          ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $authenticator = new OAuthAuthenticator($client);
        $authenticator->setAuthenticationEndpoint('');
        $authenticationToken = $authenticator->authenticate('1234', '4567');
        $this->assertEquals('MTQ0NjJkZmQ5OTM2NDE1ZTZjNGZmZjI3', $authenticationToken['access_token']);
    }

    /**
     * Test if the client can handle authentication failures
     */
    public function test_failed_authentication()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
          new Response(
              400,
              [],
              json_encode(["error_description" => "Invalid Credentials"])
          ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $authenticator = new OAuthAuthenticator($client);
        $authenticator->setAuthenticationEndpoint('');
        try {
            $authenticator->authenticate('1234', '4567');
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertStringContainsString('Invalid Credentials', $e->getMessage());
        }
    }
}
