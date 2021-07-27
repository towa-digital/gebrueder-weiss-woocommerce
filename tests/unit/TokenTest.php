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
use GbWeiss\includes\GbWeiss;
use GbWeiss\includes\OAuth\OAuthAuthenticator;
use GbWeiss\includes\OAuth\OAuthToken;


use Mockery;

/**
 * Sample test case.
 */
class TokenTest extends TestCase
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
