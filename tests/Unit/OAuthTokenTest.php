<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Towa\GebruederWeissWooCommerce\OAuth\OAuthToken;

class OAuthTokenTest extends TestCase
{
    public function test_it_can_get_the_access_token()
    {
        $token = new OAuthToken("test", time() + 3600);

        $this->assertSame("test", $token->getAccessToken());
    }

    public function test_it_can_retrieve_the_expires_in_time()
    {
        $token = new OAuthToken("test", time() + 3600);

        $this->assertSame(3600, $token->getExpiresIn());
    }

    public function test_it_can_determine_if_the_token_is_valid()
    {
        $token = new OAuthToken("test", time() + 3600);

        $this->assertTrue($token->isValid());
    }

    public function test_it_can_determine_if_the_token_is_invalid()
    {
        $token = new OAuthToken("test", time() - 3600);

        $this->assertFalse($token->isValid());
    }

    public function test_it_can_serialize_and_unserialize_the_token()
    {
        $token = new OAuthToken("test", 1628168570);
        $serialized = $token->serialize();
        $newToken = new OAuthToken("", 0);

        $newToken->unserialize($serialized);

        $this->assertSame("test", $newToken->getAccessToken());
        $this->assertSame(1628168570 - time(), $newToken->getExpiresIn());
    }
}
