<?php

namespace WPLogging;

/**
 * Register this command in your code as:
 *
 * If ( class_exists( \WP_CLI::class ) ) {
 *     \WP_CLI::add_command( 'logs', new \WPLogging\LoggerWpCliCommand, \WPLogging\LoggerWpCliCommand::registration_args() );
 * }
 */
class LoggerWpCliCommand {
	/**
	 * @return string[]
	 */
	public static function registration_args() {
		return [
			'shortdesc' => 'wp-logging CLI command.',
		];
	}

	/**
	 * Displays the logs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp logs get --qty=50 --page=1 --group="Foo Group" --type=info --search_message="Foo Log Entry"
	 *
	 * @param array<string> $args
	 * @param array<string> $assoc_args
	 * @return void
	 */
	public function get( array $args = [], array $assoc_args = [] ) {
		$qty            = 50;
		$page           = 1;
		$group          = '';
		$type           = '';
		$search_message = '';

		if ( ! empty( $assoc_args['qty'] ) ) {
			$qty = $assoc_args['qty'];
		}

		if ( ! empty( $assoc_args['page'] ) ) {
			$page = $assoc_args['page'];
		}

		if ( ! empty( $assoc_args['group'] ) ) {
			$group = $assoc_args['group'];
		}

		if ( ! empty( $assoc_args['type'] ) ) {
			$type = $assoc_args['type'];
		}

		if ( ! empty( $assoc_args['search_message'] ) ) {
			$search_message = $assoc_args['search_message'];
		}

		$logger = new \WPLogging\Logger();

		// Show newest log entries at the bottom.
		$log_entries = array_reverse( $logger->get( $qty, $page, $group, $type, $search_message ) );

		if ( empty( $log_entries ) ) {
			\WP_CLI::success( 'There are no log entries to display.' );
			exit;
		}

		// @phpstan-ignore-next-line
		\WP_CLI\Utils\format_items( 'table', $log_entries, Logger::$columns );
		\WP_CLI::success( '' );
	}

	/**
	 * Purges the logs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp logs purge
	 *
	 * @param array<string> $args
	 * @param array<string> $assoc_args
	 * @return void
	 */
	public function purge( array $args = [], array $assoc_args = [] ) {
		$logger = new \WPLogging\Logger();
		$logger->purge();
	}
}
