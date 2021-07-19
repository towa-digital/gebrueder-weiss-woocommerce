<?php
/**
 * OAuthAuthenticator
 * Used to retrieve to authenticate the client & retrieves the access token
 *
 * @package GbWeiss
 * @author Towa Digital <developer@towa.at>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0-or-later
 */

namespace GbWeiss\includes\OAuth;

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
     * The http client implementation used.
     *
     * @var Object
     */
    private $client = null;

    /**
     * Constructor.
     *
     * @param Object $client Authentication Client implemenation.
     */
    public function __construct($client)
    {
        $this->client = $client;
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
    public function authenticate(string $clientId, string $clientSecret): object
    {
        $timestampCreated = time();
        try {
            $response = $this->client->post($this->authenticationEndpoint, [
                "form_params" => [
                    "grant_type" => "client_credentials",
                    "client_id" => $clientId,
                    "client_secret" => $clientSecret
                ]
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Authentication failed: could not connect to the authentication host.');
        }
        if ($response->getStatusCode() === 400) {
            throw new \Exception('Authentication failed. ' . json_decode($response->getBody(), true)['error_description']);
        }
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Authentication failed. ');
        }

        $rawToken = json_decode($response->getBody());
        return new OAuthToken(
            $rawToken->access_token ?? null,
            $rawToken->token_type ?? null,
            $rawToken->expires_in ?? null,
            null,
            null,
            $timestampCreated,
        );
    }
}
