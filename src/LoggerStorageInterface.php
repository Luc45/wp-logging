<?php

namespace WPLogging;

interface LoggerStorageInterface {
	/**
	 * @param scalar $message
	 * @param string $type
	 * @param string $context
	 * @param string $group
	 *
	 * @return void
	 */
	public function store( $message, $type, $context = '', $group = '' );

	/**
	 * @param int    $qty
	 * @param int    $page
	 * @param string $group
	 * @param string $search
	 *
	 * @return array
	 */
	public function get( $qty = 50, $page = 1, $group = '', $search = '' );
}
