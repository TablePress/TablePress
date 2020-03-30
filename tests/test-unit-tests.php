<?php

/**
 * Tests to test that the testing framework is working properly.
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.1.0
 */
class TablePress_Test_Unit_Tests extends TablePress_TestCase {

	/**
	 * Run a simple test to ensure that the tests are running.
	 *
	 * @since 1.1.0
	 */
	public function test_true_is_true() {
		$this->assertTrue( true );
	}

	/**
	 * If these tests are being run on Travis CI, verify that the version of
	 * WordPress installed is the version that we requested.
	 *
	 * @since 1.4.0
	 */
	public function test_wp_version() {
		if ( ! getenv( 'TRAVIS' ) ) {
			$this->markTestSkipped( 'Test skipped since Travis CI was not detected.' );
		}

		$requested_version = getenv( 'WP_VERSION' );

		// For the "master" branch, get the current version number from the wordpress.org SVN server.
		if ( 'master' === $requested_version ) {
			$file = file_get_contents( 'https://develop.svn.wordpress.org/trunk/src/wp-includes/version.php' );
			preg_match( '#\$wp_version = \'([^\']+)\';#', $file, $matches );
			$requested_version = $matches[1];
		}

		/*
		 * Version string can contain various strings, like "src", "RC1", "alpha-12345" (SVN revision), etc.,
		 * which is why we only compare the major versions, in the first three characters.
		 */
		$installed_version = substr( get_bloginfo( 'version' ), 0, 3 );
		$requested_version = substr( $requested_version, 0, 3 );

		$this->assertSame( $installed_version, $requested_version );
	}

	/**
	 * Ensure that the plugin has been installed and activated.
	 *
	 * @since 1.4.0
	 */
	public function test_plugin_is_activated() {
		$this->assertTrue( is_plugin_active( 'tablepress/tablepress.php' ) );
	}

} // class TablePress_Test_Unit_Tests
