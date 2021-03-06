# Development

## TLDR;

- Gitflow is used
- PHPUnit for testing
  - install script has to be run first
- PR Templates are used if you want to make a Pull Request

## Installation

If you want to develop with this plugin, you would want to clone the repo and run `composer install` in the root directory, to install all dependencies and dev dependencies.

## Testing

The Project contains automated tests which can be run locally with `phpunit` from the commandline and the root of the plugin

### Important Note

The composer dependencies include stubs for WordPress, WordPress Tests and WooCommerce. You might have to instruct the language server to include the stubs for analysis. There is also one more caveat with `intelephense` for VSCode the stubs for WordPress are rather large. Hence you have to increase the maximum file size for indexing. Otherwise the plugin will skip indexing the subs. You have to set `intelephense.files.maxSize` to at least `5000000` since the stub file has around 4.4mb at the time of writing this.

### Setup

- install composer dependencies
- run `/bin/install-wp-test.sh` like so: [Initialize local testing in wordpress](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/#3-initialize-the-testing-environment-locally) for example:
`./bin/install-wp-tests.sh wordpress_test root root mysql latest`

## Wp cli

We rely heavily on wp-cli in our daily work. We do recommend you use it as well.