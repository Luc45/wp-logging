# wp-logging
[![Unit Tests](https://github.com/Luc45/wp-logging/actions/workflows/unit.yml/badge.svg)](https://github.com/Luc45/wp-logging/actions/workflows/unit.yml)
[![PHPCS Tests](https://github.com/Luc45/wp-logging/actions/workflows/phpcs.yml/badge.svg)](https://github.com/Luc45/wp-logging/actions/workflows/phpcs.yml)
[![PHPStan Tests (Level 6)](https://github.com/Luc45/wp-logging/actions/workflows/phpstan.yml/badge.svg)](https://github.com/Luc45/wp-logging/actions/workflows/phpstan.yml)

```php
if ( class_exists( \WP_CLI::class ) ) {
    \WP_CLI::add_command( 'logs', new \WPLogging\LoggerWpCliCommand, \WPLogging\LoggerWpCliCommand::registration_args() );
}
```