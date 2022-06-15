<?php

class LoggerTest extends WP_UnitTestCase {
	use \Spatie\Snapshots\MatchesSnapshots;

	protected function normalize_log_for_snapshot( array $result ) {
		foreach ( $result as &$r ) {
			unset( $r['created_at'] );
			unset( $r['id'] );
		}

		return $result;
	}

	public function test_add_get_log() {
		$logger = new \WPLogging\Logger();
		$logger->info( 'Foo' );

		$this->assertMatchesJsonSnapshot( $this->normalize_log_for_snapshot( $logger->get() ) );
	}

	public function test_add_multiple_logs() {
		$logger = new \WPLogging\Logger();
		$logger->info( 'Foo' );
		$logger->critical( 'Foo' );

		$this->assertMatchesJsonSnapshot( $this->normalize_log_for_snapshot( $logger->get() ) );
	}

	public function test_add_multiple_logs_with_group() {
		$logger = new \WPLogging\Logger();
		$logger->set_group( 'Foo Group' );
		$logger->info( 'Foo' );
		$logger->critical( 'Foo' );

		$this->assertMatchesJsonSnapshot( $this->normalize_log_for_snapshot( $logger->get() ) );
	}

	public function test_add_multiple_logs_with_context() {
		$logger = new \WPLogging\Logger();
		$logger->info( 'Foo', [ 'foo' => 'bar' ] );

		$this->assertMatchesJsonSnapshot( $this->normalize_log_for_snapshot( $logger->get() ) );
	}
}