<?php

namespace WPLogging;

class LoggerDatabaseStorage implements LoggerStorageInterface {
	/**
	 * A set of constants that are used internally to detect the
	 * state of the custom table used by the class to persist and
	 * manage its information.
	 */
	const TABLE_NOT_EXIST = - 1;
	const TABLE_EXISTS    = 0;
	const TABLE_CREATED   = 1;

	/**
	 * The current table state, or `null` if the current table state has never been
	 * assessed before.
	 *
	 * @var int|null
	 */
	private $table_state;

	/**
	 * Option name where we store queue table version
	 *
	 * @var string
	 */
	const TABLE_VERSION_KEY = 'wp_logging_table_version';


	/**
	 * @inheritdoc
	 */
	public function store( $message, $type, $context_json = '', $group = '' ) {
		if ( $this->check_table() === self::TABLE_NOT_EXIST ) {
			return;
		}

		global $wpdb;

		$table_name = static::get_table_name();
		$result     = $wpdb->query( $wpdb->prepare( "INSERT INTO `$table_name` (`message`, `log_type`, `log_group`, `context_json`, `created_at`) VALUES (%s, %s, %s, %s, %s)", $message, $type, $group, $context_json, gmdate( 'Y-m-d H:i:s', time() ) ) ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $result ) {
			throw new \RuntimeException( 'Could not store log entry.' );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function get( $qty = 50, $page = 1, $group = '', $type = '', $search_message = '' ) {
		if ( $this->check_table() === self::TABLE_NOT_EXIST ) {
			return new LoggerEntriesCollection();
		}

		global $wpdb;

		$table_name = static::get_table_name();
		$where      = "SELECT * FROM `$table_name` WHERE 1=1";

		if ( ! empty( $group ) ) {
			$where .= $wpdb->prepare( ' AND `log_group` = %s', $group );
		}

		if ( ! empty( $type ) ) {
			$where .= $wpdb->prepare( ' AND `log_type` = %s', $type );
		}

		if ( ! empty( $search_message ) ) {
			$where .= $wpdb->prepare( ' AND `message` LIKE %s', $wpdb->esc_like( $search_message ) );
		}

		$where .= $wpdb->prepare( ' LIMIT %d, %d', max( 0, ( $page - 1 ) ) * $qty, max( 1, $qty ) );

		$results = $wpdb->get_results( $where, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$collection = new LoggerEntriesCollection();

		foreach ( $results as $r ) {
			$collection->add(
				$r['message'],
				$r['log_type'],
				$r['log_group'],
				$r['context_json'],
				$r['created_at']
			);
		}

		return $collection;
	}

	/**
	 * Checks and reports the state of the table.
	 *
	 * If the table does not exist, then the method will try to create or update it.
	 *
	 * @param bool $force Whether to force the check on the table or trust the state
	 *                    cached from a previous check.
	 *
	 * @return int The value of one of the `TABLE` class constants to indicate the
	 *             table status.
	 */
	public function check_table( $force = false ) {
		// Early bail: Already checked in this request.
		if ( ! $force && $this->table_state !== null ) {
			return $this->table_state;
		}

		$this->table_state = self::TABLE_NOT_EXIST;

		$table_version = get_option( self::TABLE_VERSION_KEY, '0.0.0' );

		// Trigger an update or creation if either the table should be updated, or it does not exist.
		if ( version_compare( $table_version, $this->get_table_version(), '<' ) || ! $this->table_exists() ) {
			$table_state = $this->update_table();

			if ( $table_state === self::TABLE_EXISTS ) {
				// The table now exists.
				$this->table_state = self::TABLE_EXISTS;

				// Just created.
				return self::TABLE_CREATED;
			}
		}

		$this->table_state = $this->table_exists() ? self::TABLE_EXISTS : self::TABLE_NOT_EXIST;

		return $this->table_state;
	}

	/**
	 * Updates the Queue table schema to the latest version, non destructively.
	 *
	 * The use of the `dbDelta` method will ensure the table is updated non-destructively
	 * and only if required.
	 *
	 * @return int The value of one of the `TABLE` constants to indicate the result of the
	 *             update.
	 */
	private function update_table() {
		global $wpdb;

		$table_sql = $this->get_create_table_query();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $table_sql, true );

		$this->update_table_version_option( $this->get_table_version() );

		return self::TABLE_EXISTS;
	}

	/**
	 * Returns the name of the table used by the Queue to store the actions and their state.
	 *
	 * @return string The prefixed name of the table used by the Queue to store the actions
	 *                and their state.
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wplogging';
	}

	/**
	 * Updates the version of the table in the plugin options to make sure
	 * it will not be updated on next check.
	 *
	 * @param string $table_version A semantic version format representing the
	 *                             table version to write to the plugin options.
	 *
	 * @return void The method does not return any value and will have the
	 *              side-effect of updating the plugin options.
	 */
	private function update_table_version_option( $table_version ) {
		update_option( self::TABLE_VERSION_KEY, $table_version, false );
	}

	/**
	 * Checks whether the table exists or not.
	 *
	 * @return bool Whether the table exists or not.
	 */
	public function table_exists() {
		global $wpdb;
		$table_name = self::get_table_name();
		$result     = $wpdb->query( "SHOW TABLES LIKE '$table_name'" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $result === false ) {
			return false;
		}

		$value = $wpdb->get_row( $result ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $value === [ $table_name ];
	}

	/**
	 * Return the Queue table creation SQL code.
	 *
	 * @return string The Queue table creation SQL code.
	 */
	private function get_create_table_query() {
		global $wpdb;
		$collate     = $wpdb->collate;
		$queue_table = self::get_table_name();
		$table_sql   = "CREATE TABLE `$queue_table` (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            message LONGTEXT DEFAULT NULL,
            context_json LONGTEXT DEFAULT NULL,
            log_type VARCHAR(1000) DEFAULT NULL,
            log_group VARCHAR(1000) DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id)
            ) COLLATE {$collate}";

		return $table_sql;
	}

	/**
	 * Drops the custom table used by the Queue to store actions.
	 *
	 * Dropping the table means, implicitly, also losing all the Actions
	 * stored there.
	 *
	 * @return bool Whether the table dropping was successful or not.
	 */
	public function drop_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		$query      = "DROP TABLE IF EXISTS `$table_name`";
		$wpdb->query( $query, true ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->table_state = self::TABLE_NOT_EXIST;

		return ! $this->table_exists();
	}

	/**
	 * Returns the latest table version.
	 *
	 * @return string The latest table version, in semantic format.
	 */
	private function get_table_version() {
		return '1.0.0';
	}

	/**
	 * Deletes all log entries.
	 *
	 * @return bool
	 */
	public function purge() {
		global $wpdb;
		$table_name = self::get_table_name();
		$query      = "TRUNCATE `$table_name`";
		$result     = $wpdb->query( $query, true ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result === true;
	}
}
