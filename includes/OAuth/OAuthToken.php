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
    private $accessToken = null;

    /**
     * Expires timestamp
     *
     * @var int
     */
    private $expires = null;

    /**
     * Constructor.
     *
     * @param string $accessToken The access token.
     * @param int    $expires expires The expiration timestamp.
     */
    public function __construct(string $accessToken, int $expires)
    {
        $this->accessToken = $accessToken;
        $this->expires = $expires;
    }

    /**
     * Retrieves the Access Token.
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
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
        return $this->accessToken && ($this->expires > time());
    }

    /**
     * Serializes the token
     *
     * @return string
     */
    public function serialize()
    {
        return serialize([$this->accessToken, $this->expires]);
    }

    /**
     * Restores the token from serialized data
     *
     * @param string $data Serialized token data.
     * @return void
     */
    public function unserialize($data)
    {
        list($this->accessToken, $this->expires) = unserialize($data);
    }
}
