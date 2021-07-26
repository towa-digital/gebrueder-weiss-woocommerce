<?php
/**
 * OAuthToken
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace GbWeiss\includes\OAuth;

defined('ABSPATH') || exit;
/**
 * OAuthAuthenticator Class
 */
class OAuthToken
{
    /**
     * Access_token
     *
     * @var string $access_token Token.
     */
    private $access_token = null;

    /**
     * Token_type
     *
     * @var string $token_type Token Type.
     */
    private $token_type = null;

    /**
     * Expires_in
     *
     * @var string $expires_in Expires in.
     */
    private $expires_in = null;

    /**
     * Refresh_token
     *
     * @var string $refresh_token Refresh Token.
     */
    private $refresh_token = null;

    /**
     * Scope
     *
     * @var string $scope Scope.
     */
    private $scope = null;

    /**
     * Constructor.
     *
     * @param string $access_token access_token.
     * @param string $token_type token_type.
     * @param int    $expires expires timestamp.
     * @param string $refresh_token refresh_token.
     * @param string $scope scope.
     */
    public function __construct(string $access_token, string $token_type, int $expires, string $refresh_token = null, string $scope = null)
    {
        $this->access_token = $access_token;
        $this->token_type = $token_type;
        $this->expires = $expires;
        $this->refresh_token = $refresh_token;
        $this->scope = $scope;
    }

    /**
     * Retrieves the Access Token.
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->access_token;
    }

    /**
     * Retrieves the Token Type.
     *
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->token_type;
    }

    /**
     * Retrieves the Time until the token is expired.
     *
     * @return string
     */
    public function getExpiresIn(): string
    {
        return $this->expires;
    }

    /**
     * Retrieves the Refresh token.
     *
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    /**
     * Retrieves the Scope.
     *
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Validates the Token.
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return $this->access_token && ($this->expires > time());
    }
}
