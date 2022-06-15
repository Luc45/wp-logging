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

	/**
	 * @var array<string> The array keys of a log entry array.
	 */
	public static $columns = [
		'message',
		'log_type',
		'log_group',
		'created_at',
	];

	/** @var LoggerStorageInterface|null $storage */
	protected $storage;

	/** @var LoggerStorageInterface|null $fallback_storage */
	protected $fallback_storage;

	/**
	 * @return void
	 */
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

	/**
	 * @return LoggerStorageInterface
	 */
	public function make_error_log_storage() {
		return new LoggerErrorLogStorage();
	}

	/**
	 * @param int    $qty How many results per page.
	 * @param int    $page Page parameter.
	 * @param string $group Which log group to retrieve results for.
	 * @param string $type Which log type to retrieve results for.
	 * @param string $search_message Which log message to search for.
	 *
	 * @return array<scalar>
	 */
	public function get( $qty = 50, $page = 1, $group = '', $type = '', $search_message = '' ) {
		return $this->storage->get( $qty, $page, $group, $type, $search_message );
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string                          $level The level of the log.
	 * @param string                          $message The message to log.
	 * @param array<scalar|\JsonSerializable> $context Additional context data related to this log entry.
	 *
	 * @return void
	 */
	public function log( $level, $message, array $context = [] ) {
		$this->register();

		try {
			$this->storage->store( $message, $level, wp_json_encode( $context ) );
		} catch ( \Exception $e ) {
			// Early bail: Fallback storage disabled.
			if ( is_null( $this->fallback_storage ) ) {
				return;
			}

			try {
				$this->fallback_storage->store( $message, $level, wp_json_encode( $context ) );
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				// no-op.
			}
		}
	}

	/**
	 * @return void
	 */
	public function purge() {
		$this->storage->purge();
		$this->fallback_storage->purge();
	}

	/**
	 * System is unusable.
	 *
	 * @param string                          $message
	 * @param array<scalar|\JsonSerializable> $context
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
	 * @param string                          $message
	 * @param array<scalar|\JsonSerializable> $context
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
	 * @param string                          $message
	 * @param array<scalar|\JsonSerializable> $context
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
	 * @param string                          $message
	 * @param array<scalar|\JsonSerializable> $context
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
	 * @param string                          $message
	 * @param array<scalar|\JsonSerializable> $context
	 *
	 * @return void
	 */
	public function warning( $message, array $context = [] ) {
		$this->log( static::WARNING, $message, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string                          $message
	 * @param array<scalar|\JsonSerializable> $context
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
	 * @param string                          $message
	 * @param array<scalar|\JsonSerializable> $context
	 *
	 * @return void
	 */
	public function info( $message, array $context = [] ) {
		$this->log( static::INFO, $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string                          $message
	 * @param array<scalar|\JsonSerializable> $context
	 *
	 * @return void
	 */
	public function debug( $message, array $context = [] ) {
		$this->log( static::DEBUG, $message, $context );
	}
}
