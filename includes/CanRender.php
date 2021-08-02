<?php
/**
 * Can Render interface
 *
 * @package GbWeissInterfaces
 */

namespace Towa\GebruederWeissWooCommerce;

defined('ABSPATH') || exit;

interface CanRender
{
    /**
     * Renders output
     *
     * @return void
     */
    public function render(): void;

    /**
     * Gets Data to Render from current Class
     *
     * @return array
     */
    public function getDataToRender(): array;
}
