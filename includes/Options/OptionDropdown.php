<?php
/**
 * Dropdown for option pages
 *
 * @package Options
 */

namespace Towa\GebruederWeissWooCommerce\Options;

/**
 * Dropdown for option pages
 */
class OptionDropdown extends Option
{
     /**
      * Options for the dropdown
      *
      * @var array
      */
    public $options;

    /**
     * Option Constructor
     *
     * @param string   $name Name of the option.
     * @param string   $slug Slug used for storing the option in the database.
     * @param string   $description Description for the option.
     * @param string   $group Group the option belongs to.
     * @param array    $options Available options for the dropdown.
     * @param callable $sanitizeCallback Callback used for sanitization.
     * @param [type]   $default Default value of option.
     */
    public function __construct(
        string $name,
        string $slug,
        string $description,
        string $group,
        array $options,
        callable $sanitizeCallback = null,
        $default = null
    ) {
        parent::__construct($name, $slug, $description, $group, 'dropdown', $sanitizeCallback, $default);
        $this->options = $options;
    }

    /**
     * Adds the options given to the Option Dropdown.
     *
     * @param array $options Options to add to the dropdown.
     */
    public function addOptions(array $options): void
    {
        $this->options = array_merge($options, $this->options);
    }
}
