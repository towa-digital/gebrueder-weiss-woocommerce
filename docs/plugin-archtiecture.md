# Plugin Architecture

The plugin is based on the [Gebrüder Weiss PHP SDK](https://packagist.org/packages/towa/gbw-sdk).
This package contains a PHP client for the Gebrüder Weiss API that was generated based on the API schema definition.

As with every WordPress plugin, the main entry point is a PHP file called the same as the plugin.
In this case, this is the `gebrueder-weiss-woocommerce.php` file.
Its primary responsibility is to bootstrap the plugin and register the necessary hooks for the plugin.

The plugin can be broken down into a few components:
- FailedRequestQueue: This component manages failed requests and handles their retries.
- OAuth: This component is responsible for generating an access token whenever the current access token expires.
- Options: This component provides the functionality to create setting pages.
