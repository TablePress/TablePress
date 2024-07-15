<?php
/**
 * Tests to test that the testing framework is working properly.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.1.0
 */

/**
 * Tests to test that the testing framework is working properly.
 *
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
	public function test_true_is_true(): void {
		$this->assertTrue( true );
	}

	/**
	 * Verify that the installed version of WordPress is the version that was requested.
	 *
	 * @since 1.4.0
	 */
	public function test_wp_version(): void {
		$requested_version = getenv( 'WP_VERSION' );

		// For the "trunk" branch, get the current version number from the wordpress.org SVN server.
		if ( 'trunk' === $requested_version ) {
			// Requests to wordpress.org servers require a User agent to be set.
			$options = array(
				'http' => array(
					'user_agent' => 'TablePress Unit Tests',
				),
			);
			$context = stream_context_create( $options );
			$file = file_get_contents( 'https://develop.svn.wordpress.org/trunk/src/wp-includes/version.php', false, $context );
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
	public function test_plugin_is_activated(): void {
		$this->assertTrue( is_plugin_active( 'tablepress/tablepress.php' ) );
	}

} // class TablePress_Test_Unit_Tests
