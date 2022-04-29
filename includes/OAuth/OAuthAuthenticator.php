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
use Towa\GebruederWeissWooCommerce\Exceptions\AuthenticationFailedException;
use Towa\GebruederWeissWooCommerce\SettingsRepository;

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
     * Repository to retrieve plugin settings.
     *
     * @var SettingsRepository
     */
    private $settingsRepository = null;

    /**
     * Constructor.
     *
     * @param GenericProvider    $authProvider client object.
     * @param SettingsRepository $settingsRepository Repository for reading plugin settings.
     */
    public function __construct(GenericProvider $authProvider, SettingsRepository $settingsRepository)
    {
        $this->authProvider = $authProvider;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Returns the access token
     *
     * @throws AuthenticationFailedException Thrown if an error occurred.
     * @return OAuthToken
     */
    public function authenticate(): OAuthToken
    {
        try {
            $leagueToken = $this->authProvider->getAccessToken('client_credentials', [
                'client_id' => $this->settingsRepository->getClientId(),
                'client_secret' => $this->settingsRepository->getClientSecret(),
            ]);

            return new OAuthToken(
                $leagueToken->getToken(),
                $leagueToken->getExpires(),
            );
        } catch (IdentityProviderException $e) {
            throw new AuthenticationFailedException('Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Checks whether the currently stored auth token is valid and requests
     * a new token if the current one is not valid.
     *
     *  @throws AuthenticationException Thrown if an error occurred.
     */
    public function updateAuthTokenIfNecessary(): void
    {
        $currentToken = $this->settingsRepository->getAccessToken();

        if (!is_null($currentToken) && $currentToken->isValid()) {
            return;
        }

        $freshToken = $this->authenticate();

        $this->settingsRepository->setAccessToken($freshToken);
    }
}
