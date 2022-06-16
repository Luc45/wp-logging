<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Cd_Manager
 */

require_once '/var/wplogging/wp-logging/vendor/autoload.php';

$_tests_dir = '/var/wplogging/tests/tmp/wordpress-tests-lib';

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
    echo "Could not find {$_tests_dir}/includes/functions.php, have you run make setup ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
