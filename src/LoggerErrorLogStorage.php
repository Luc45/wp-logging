<?php

namespace WPLogging;

class LoggerErrorLogStorage implements LoggerStorageInterface {
	/**
	 * @inheritdoc
	 */
	public function store( $message, $type, $context = '', $group = '' ) {
		// [INFO - 2022-15-06 12:00:00] Foo Group - Foo Logging Message (['foo' => 'bar'])
		error_log( sprintf( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			'[%s - %s] %s%s (%s)',
			strtoupper( $type ),
			gmdate( 'Y-m-d H:i:s', time() ),
			$message, empty( $group ) ? '' : "$group - ",
			wp_json_encode( $context )
		) );
	}

	/**
	 * @inheritdoc
	 */
	public function get( $qty = 50, $page = 1, $group = '', $type = '', $search_message = '' ) {
		return new LoggerEntriesCollection();
	}

	/**
	 * @inheritdoc
	 */
	public function purge() {
		return false;
	}
}
