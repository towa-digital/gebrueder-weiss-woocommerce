<?php
/**
 * OAuthAuthenticator
 * Retrieves the Access Token.
 * Uses league/oauth2-client
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace GbWeiss\includes\OAuth;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * OAuthAuthenticator Class
 */
class OAuthAuthenticator
{
    /**
     * The endpoint used to authenticate against.
     *
     * @var string
     */
    private $authenticationEndpoint = null;

    /**
     * Client Provider Object.
     *
     * @param object $authProvider the oAuth authProvider.
     */

    /**
     * Constructor.
     *
     * @param object $authProvider client object.
     */
    public function __construct($authProvider)
    {
        $this->authProvider = $authProvider;
    }

    /**
     * Sets the authentication endpoint.
     *
     * @param string $endpoint The endpoint used to authenticate against.
     * @return void
     */
    public function setAuthenticationEndpoint(string $endpoint)
    {
        $this->authenticationEndpoint = $endpoint;
    }

    /**
     * Returns the access token
     *
     * @param string $clientId the id of the client.
     * @param string $clientSecret the secret key of the client.
     * @throws \Exception With message.
     * @return string
     */
    public function authenticate(string $clientId, string $clientSecret): OAuthToken
    {
        try {
            $timestampCreated = time();
            $leagueToken = $this->authProvider->getAccessToken('client_credentials', [
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ]);

            return new OAuthToken(
                $leagueToken->getToken(),
                'Bearer',
                $leagueToken->getExpires(),
                $leagueToken->getRefreshToken()
            );
        } catch (IdentityProviderException $e) {
            throw new \Exception('Authentication failed: ' . $e->getMessage());
        }
    }
}
