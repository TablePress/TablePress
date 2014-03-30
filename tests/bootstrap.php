<?php
/**
 * Bootstrap the plugin unit testing environment.
 */

// Activates TablePress in WordPress so it can be tested.
$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'tablepress/tablepress.php' ),
);

// If the develop repo location is defined (as WP_DEVELOP_DIR), use that location.
// Otherwise, we'll just assume that this plugin is installed in a WordPress develop SVN checkout.
if( false !== getenv( 'WP_TESTS_DIR' ) ) {
	require getenv( 'WP_TESTS_DIR' ) . 'includes/bootstrap.php';
} else {
	require '../../../../../tests/phpunit/includes/bootstrap.php';
}

/**
 * Provide some helper classes and functions for unit testing.
 */
class TablePress_TestCase extends WP_UnitTestCase {

	/**
	 * Set variables for a faked HTTP POST request.
	 */
	function set_post( $key, $value ) {
		// Add slashing as expected by the PHP setting.
		if ( get_magic_quotes_gpc() ) {
			$value = addslashes( $value );
		}
		$_POST[ $key ] = $_REQUEST[ $key ] = $value;
	}

	/**
	 * Unset variables from a faked HTTP POST request.
	 */
	function unset_post( $key ) {
		unset( $_POST[ $key ], $_REQUEST[ $key ] );
	}
}
