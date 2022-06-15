<?php

namespace WPLogging;

class LoggerErrorLogStorage implements LoggerStorageInterface {
	public function store( $message, $type, $context = '', $group = '' ) {
		error_log( sprintf(
			// [INFO - 2022-15-06 12:00:00] Foo Group - Foo Logging Message (['foo' => 'bar'])
			'[%s - %s] %s%s (%s)',
			strtoupper( $type ),
			date( 'Y-m-d H:i:s', time() ),
			$message, empty( $group ) ? '' : "$group - ",
			wp_json_encode( $context )
			)
		);
	}

	public function get( $qty = 50, $page = 1, $group = '', $search = '' ) {
		return [];
	}
}
