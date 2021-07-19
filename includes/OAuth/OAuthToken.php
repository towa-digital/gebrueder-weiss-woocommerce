<?php
/**
 * OAuthToken
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace GbWeiss\includes\OAuth;

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
     * Timestamp Created
     *
     * @var string $timestampCreated timestamp.
     */
    private $timestampCreated = null;

    /**
     * Constructor.
     *
     * @param string $access_token access_token.
     * @param string $token_type token_type.
     * @param string $expires_in expires_in.
     * @param string $refresh_token refresh_token.
     * @param string $scope scope.
     * @param int    $timestampCreated timestamp created.
     */
    public function __construct(string $access_token, string $token_type, string $expires_in, string $refresh_token = null, string $scope = null, int $timestampCreated)
    {
        $this->access_token = $access_token;
        $this->token_type = $token_type;
        $this->expires_in = (int) $expires_in;
        $this->refresh_token = $refresh_token;
        $this->scope = $scope;
        $this->timestampCreated = $timestampCreated;
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
        return $this->expires_in;
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
        return $this->access_token && (($this->timestampCreated + $this->expires_in) < time());
    }
}
