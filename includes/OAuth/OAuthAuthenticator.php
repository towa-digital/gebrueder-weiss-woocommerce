<?php
/**
 * OAuthAuthenticator
 * Retrieves the Access Token.
 * Uses league/oauth2-client
 *
 * @package OAuth
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace Towa\GebruederWeissWooCommerce\OAuth;

defined('ABSPATH') || exit;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * OAuthAuthenticator Class
 */
class OAuthAuthenticator
{
    /**
     * Client Provider Object.
     *
     * @var GenericProvider $authProvider The oAuth authProvider.
     */
    private $authProvider = null;

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
