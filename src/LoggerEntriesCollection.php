<?php

namespace WPLogging;

class LoggerEntriesCollection {
	/** @var array<array<scalar>> $logs */
	protected $logs = [];

	/**
	 * Normalize log entries in a specific format.
	 *
	 * @param string $message
	 * @param string $type
	 * @param string $group
	 * @param string $context_json
	 * @param string $date
	 *
	 * @return void
	 */
	public function add( $message, $type, $group, $context_json, $date ) {
		$this->logs[] = [
			'message'      => $message,
			'type'         => $type,
			'group'        => $group,
			'context_json' => $context_json,
			'date'         => $date,
		];
	}

	/**
	 * @return array<array<scalar>> The log entries.
	 */
	public function to_array() {
		return $this->logs;
	}
}
