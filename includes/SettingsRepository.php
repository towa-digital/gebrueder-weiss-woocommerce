<?php
/**
 * Settings Repository
 *
 * Reads plugin options from the wordpress options
 *
 * @package Plugin
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

use Towa\GebruederWeissWooCommerce\Options\Option;

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
     * Updates the access token in the wordpress options.
     *
     * @param string $token The access token.
     * @return void
     */
    public function setAccessToken(string $token): void
    {
        $this->setOption("accessToken", $token);
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
     * Reads the customer id from the plugin settings.
     *
     * @return integer|null
     */
    public function getCustomerId(): ?int
    {
        return $this->getOption("customer_id");
    }

    /**
     * Reads the wordpress site URL from the options.
     *
     * @return string|null
     */
    public function getSiteUrl(): ?string
    {
        return \get_site_url();
    }

    /**
     * Reads the plugin option with the passed name from the wordpress options
     *
     * @param string $name The name of the option.
     * @return mixed
     */
    private function getOption(string $name)
    {
        return \get_option(Option::OPTIONS_PREFIX . $name, null);
    }

    /**
     * Sets an wordpress option
     *
     * @param string $name Name of the option.
     * @param mixed  $value New value for the option.
     * @return void
     */
    private function setOption(string $name, $value): void
    {
        \update_option(Option::OPTIONS_PREFIX . $name, $value);
    }
}
