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

### Setup

- install composer dependencies
- run `/bin/install-wp-test.sh` like so: [Initialize local testing in wordpress](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/#3-initialize-the-testing-environment-locally)

## Wp cli

We rely heavily on wp-cli in our daily work. We do recommend you use it as well. 