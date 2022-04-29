<?php
/**
 * OAuthToken
 *
 * @package OAuth
 */

namespace Towa\GebruederWeissWooCommerce\OAuth;

use Serializable;

defined('ABSPATH') || exit;

/**
 * OAuth Token
 */
class OAuthToken implements Serializable
{
    /**
     * Access Token
     *
     * @var string
     */
    private $token = null;

    /**
     * Expires timestamp
     *
     * @var int
     */
    private $expires = null;

    /**
     * Constructor.
     *
     * @param string $token The access token.
     * @param int    $expires expires The expiration timestamp.
     */
    public function __construct(string $token, int $expires)
    {
        $this->token = $token;
        $this->expires = $expires;
    }

    /**
     * Retrieves the Access Token.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Retrieves the Time until the token is expired.
     *
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expires - time();
    }

    /**
     * Validates the Token.
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return $this->token && ($this->expires > time());
    }

    /**
     * Serializes the token
     *
     * @return string
     */
    public function serialize()
    {
        return json_encode([
            "token" => $this->token,
            "expires" => $this->expires,
        ]);
    }

    /**
     * Restores the token from serialized data
     *
     * @param string $data Serialized token data.
     * @return void
     */
    public function unserialize($data)
    {
        $deserialized = json_decode($data);
        $this->token = $deserialized->token;
        $this->expires = $deserialized->expires;
    }
}
