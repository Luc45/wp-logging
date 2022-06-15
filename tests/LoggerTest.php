<?php

class LoggerTest extends WP_UnitTestCase {
	use \Spatie\Snapshots\MatchesSnapshots;

	public function test_add_get_log() {
		$logger = new \WPLogging\Logger();
		$logger->info( 'Foo' );

		$result = $logger->get();

		// Normalize data for snapshot testing.
		foreach ( $result as &$r ) {
			unset( $r['created_at'] );
			unset( $r['id'] );
		}

		$this->assertMatchesJsonSnapshot( $result );
	}
}