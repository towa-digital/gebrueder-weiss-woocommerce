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
     * @throws \Exception With message.
     * @return string
     */
    public function authenticate(): OAuthToken
    {
        try {
            $leagueToken = $this->authProvider->getAccessToken('client_credentials', [
                'clientId' => $this->settingsRepository->getClientId(),
                'clientSecret' => $this->settingsRepository->getClientSecret(),
            ]);

            return new OAuthToken(
                $leagueToken->getToken(),
                $leagueToken->getExpires(),
            );
        } catch (IdentityProviderException $e) {
            throw new \Exception('Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Checks whether the currently stored auth token is valid and requests
     * a new token if the current one is not valid.
     *
     *  @throws \Exception If the token could not be saved.
     */
    public function updateAuthTokenIfNecessary(): void
    {
        $currentToken = $this->settingsRepository->getAccessToken();

        if ($currentToken->isValid()) {
            return;
        }

        $freshToken = $this->authenticate();

        $this->settingsRepository->setAccessToken($freshToken);
    }
}
