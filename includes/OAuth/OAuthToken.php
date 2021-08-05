<?php
/**
 * OAuthToken
 *
 * @package OAuth
 */

namespace Towa\GebruederWeissWooCommerce\OAuth;

defined('ABSPATH') || exit;

/**
 * OAuth Token
 */
class OAuthToken
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
}
