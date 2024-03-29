<?php
/**
 * Option
 *
 * Class to instatiate an Option
 *
 * @package Options
 */

namespace Towa\GebruederWeissWooCommerce\Options;

defined('ABSPATH') || exit;

use Towa\GebruederWeissWooCommerce\Contracts\CanRender;
use Towa\GebruederWeissWooCommerce\Support\TwigEnvironment;
use Towa\GebruederWeissWooCommerce\Plugin;

/**
 * Option Class
 */
class Option implements CanRender
{
    /**
     * Prefix used for Option
     */
    public const OPTIONS_PREFIX = 'gbw_';

    /**
     * Value of the Option
     *
     * @var mixed
     */
    public $value;

    /**
     * Option Name
     *
     * @var string
     */
    public $name;

    /**
     * Group Name
     *
     * @var string
     */
    public $group;

    /**
     * Description
     *
     * @var string
     */
    public $description;

    /**
     * Type of option
     *
     * Parameter type of wordpress's register_setting api.
     * Can be one of the following:
     *   - string, boolean, integer, number, array, object
     *
     * @var string
     */
    public $type;

    /**
     * Callable sanitation Callback
     *
     * @var callable|null
     */
    public $sanitizeCallback;

    /**
     * Default Value
     *
     * @var mixed
     */
    public $default;

    /**
     * Option Slug
     *
     * Should not contain special characters or whitespaces.
     *
     * @var string
     */
    public $slug;

    /**
     * Option Constructor
     *
     * @param string   $name Name of Option.
     * @param string   $slug Slug used for Option registering in Databse.
     * @param string   $description Description used for Option.
     * @param string   $group Group the Option belongs to.
     * @param string   $type Type of Option.
     * @param callable $sanitizeCallback Callback used for sanitization.
     * @param [type]   $default Default Value of Option.
     */
    public function __construct(
        string $name,
        string $slug,
        string $description,
        string $group,
        string $type = 'string',
        callable $sanitizeCallback = null,
        $default = null
    ) {
        $this->name = $name;

        // prefix all options with gbw.
        $this->slug = self::OPTIONS_PREFIX . $slug;

        $this->type = $type;
        $this->group = $group;
        $this->description = $description;
        $this->default = $default;
        $this->sanitizeCallback = $sanitizeCallback;
        $this->addActions();
        $this->value = $this->typeCast(get_option($this->slug));
    }

    /**
     * Adds Wordpress Actions
     *
     * @return void
     */
    private function addActions(): void
    {
        \add_action('admin_init', [$this, 'registerOption']);
    }

    /**
     * Register Wordpress Setting for option
     *
     * @return void
     */
    public function registerOption(): void
    {
        \register_setting(
            $this->group,
            $this->slug,
            [
                'type' => $this->type,
                'description' => $this->description,
                'sanitize_callback' => $this->sanitizeCallback ??
                function ($text) {
                    return sanitize_text_field($text);
                },
                'show_in_rest' => false,
                'default' => $this->default
            ]
        );

        \add_settings_field(
            $this->slug,
            $this->name,
            [$this, 'render'],
            Plugin::OPTION_PAGE_SLUG,
            $this->group,
        );
    }

    /**
     * Get the Value of the Option
     *
     * @return int|string|object
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the Value of the Option for usage as html Attribute
     *
     * @return string
     */
    public function getValueAsAttribute(): string
    {
        return esc_attr($this->value);
    }

    /**
     * Get Date of Option to be rendered
     *
     * @return array
     */
    public function getDataToRender(): array
    {
        return ['option' => $this];
    }

    /**
     * Render Option
     *
     * @return void
     */
    public function render(): void
    {
        echo TwigEnvironment::render(
            'options/' . $this->type . '.twig',
            $this->getDataToRender()
        );
    }

    /**
     * Type Cast Value
     *
     * @param mixed $value Value to be type casted.
     * @return boolean|integer|float|array|object|string
     */
    private function typeCast($value)
    {
        switch ($this->type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'number':
                return (float) $value;
            case 'array':
                return (array) $value;
            case 'object':
                return (object) $value;
            default:
                return $value;
        }
    }
}
