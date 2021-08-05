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
     * Token Type
     *
     * @var string
     */
    private $tokenType = null;

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
     * @param string $tokenType The token type.
     * @param int    $expires expires The expiration timestamp.
     */
    public function __construct(string $accessToken, string $tokenType, int $expires)
    {
        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
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
     * Retrieves the Token Type.
     *
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
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
