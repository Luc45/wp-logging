<?php

namespace WPLogging;

interface LoggerStorageInterface {
	public function store( $message, $type, array $context = [], $group = '' );

	public function get( $qty = 50, $page = 1, $group = '', $search = '' );
}
