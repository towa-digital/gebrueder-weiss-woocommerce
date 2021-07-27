<?php
/**
 * Settings Repository
 *
 * Reads plugin options from the wordpress options
 *
 * @package GbWeiss
 */

namespace GbWeiss\includes;

defined('ABSPATH') || exit;

/**
 * Settings Repository
 *
 * Reads plugin options from the wordpress options
 */
class SettingsRepository
{
    /**
     * Reads the client id from the wordpress options
     *
     * @return string|null
     */
    public function getClientId(): ?string
    {
        return $this->getOption("client_id");
    }

    /**
     * Reads the client secret from the wordpress options
     *
     * @return string|null
     */
    public function getClientSecret(): ?string
    {
        return $this->getOption("client_secret");
    }

    /**
     * Reads the access token form the wordpress options
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->getOption("accessToken");
    }

    /**
     * Reads the fulfillment state from the wordpress options
     *
     * @return string|null
     */
    public function getFulfillmentState(): ?string
    {
        return $this->getOption("fulfillmentState");
    }

    /**
     * Reads the fulfilled state from the wordpress options
     *
     * @return string|null
     */
    public function getFulfilledState(): ?string
    {
        return $this->getOption("fulfilledState");
    }

    /**
     * Reads the fulfillment error state from the wordpress options
     *
     * @return string|null
     */
    public function getFulfillmentErrorState(): ?string
    {
        return $this->getOption("fulfillmentErrorState");
    }

    /**
     * Reads the plugin option with the passed name from the wordpress options
     *
     * @param string $name The name of the option.
     * @return string|null
     */
    private function getOption(string $name): ?string
    {
        return \get_option(Option::OPTIONS_PREFIX . $name, null);
    }
}