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

defined('ABSPATH') || exit;

use League\OAuth2\Client\Provider\GenericProvider;
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
     * @param GenericProvider $authProvider the oAuth authProvider.
     */

    /**
     * Constructor.
     *
     * @param GenericProvider $authProvider client object.
     */
    public function __construct(GenericProvider $authProvider)
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
    public function getToken(string $clientId, string $clientSecret): OAuthToken
    {
        try {
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
