<?php

namespace WPLogging;

class Logger implements LoggerInterface {
	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	/** @var LoggerStorageInterface $storage */
	protected $storage;

	/** @var LoggerStorageInterface $fallback_storage */
	protected $fallback_storage;

	protected function register() {
		// Early bail: Already registered.
		if ( ! is_null( $this->storage ) ) {
			return;
		}

		$this->storage          = call_user_func( apply_filters( 'wplogging_storage', [ $this, 'make_database_storage' ] ) );
		$this->fallback_storage = call_user_func( apply_filters( 'wplogging_fallback_storage', [ $this, 'make_error_log_storage' ] ) );
	}

	/**
	 * @return LoggerStorageInterface
	 */
	public function make_database_storage() {
		return new LoggerDatabaseStorage();
	}

	public function get( $qty = 50, $page = 1, $group = '', $search = '' ) {
		return $this->storage->get( $qty, $page, $group, $search );
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function log( $level, $message, array $context = [] ) {
		$this->register();

		try {
			$this->storage->store( $message, $level, wp_json_encode( $context ) );
		} catch ( \Exception $e ) {
			$this->fallback_storage->store( $message, $level, wp_json_encode( $context ) );
		}
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function emergency( $message, array $context = [] ) {
		$this->log( static::EMERGENCY, $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function alert( $message, array $context = [] ) {
		$this->log( static::ALERT, $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function critical( $message, array $context = [] ) {
		$this->log( static::CRITICAL, $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function error( $message, array $context = [] ) {
		$this->log( static::ERROR, $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function warning( $message, array $context = [] ) {
		$this->log( static::WARNING, $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function notice( $message, array $context = [] ) {
		$this->log( static::NOTICE, $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function info( $message, array $context = [] ) {
		$this->log( static::INFO, $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function debug( $message, array $context = [] ) {
		$this->log( static::DEBUG, $message, $context );
	}
}
