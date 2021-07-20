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

namespace GbWeiss\includes;

use stdClass;

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
     * Constructor.
     */
    public function __construct()
    {
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
    public function authenticate(string $clientId, string $clientSecret): string
    {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'redirectUri'             => 'https://my.example.com/your-redirect-url/',
            'urlAuthorize'            => null,
            'urlAccessToken'            => 'http://18019fdce8ff:8887/token',
            'urlResourceOwnerDetails' => null
        ]);
        try {
            $accessToken = $provider->getAccessToken('client_credentials', [
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ]);
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            return '';
        }
        return $accessToken;
    }
}
