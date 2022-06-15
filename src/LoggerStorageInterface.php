<?php

namespace WPLogging;

interface LoggerStorageInterface {
	/**
	 * @param string $message The log message to store.
	 * @param string $type The type of the log.
	 * @param string $context_json Additional context data in JSON.
	 * @param string $group The group of the log message (Such as where it took place).
	 *
	 * @return void
	 */
	public function store( $message, $type, $context_json = '', $group = '' );

	/**
	 * @param int    $qty How many results per page.
	 * @param int    $page Page parameter.
	 * @param string $group Which log group to retrieve results for.
	 * @param string $type Which log type to retrieve results for.
	 * @param string $search_message Which log message to search for.
	 *
	 * @return array<scalar>
	 */
	public function get( $qty = 50, $page = 1, $group = '', $type = '', $search_message = '' );
}
