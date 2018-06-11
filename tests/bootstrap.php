<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.1.0
 */

/*
 * If the WP unit tests location is defined (as WP_TESTS_DIR), use that location.
 * Otherwise, we assume that this plugin is installed in a WordPress Develop repository checkout.
 */
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
} else {
	$wp_tests_dir = '../../../../../tests/phpunit';
}

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'tablepress/tablepress.php' ),
);

require_once $wp_tests_dir . 'includes/functions.php';

// Activates TablePress in WordPress so it can be tested.
function tablepress_tests_init() {
	require dirname( dirname( __FILE__) ) . '/tablepress.php';
}
tests_add_filter( 'plugins_loaded', 'tablepress_tests_init' );

require $wp_tests_dir . 'includes/bootstrap.php';

/**
 * TablePress Unit Testing Testcase class.
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.1.0
 */
class TablePress_TestCase extends WP_UnitTestCase {

	/**
	 * Set variables for a faked HTTP POST request.
	 *
	 * @since 1.1.0
	 *
	 * @param string $key   Name of the POST variable.
	 * @param string $value Value of the POST variable.
	 */
	public function set_post( $key, $value ) {
		// Add slashing as expected by the PHP setting.
		if ( get_magic_quotes_gpc() ) {
			$value = addslashes( $value );
		}
		$_POST[ $key ] = $_REQUEST[ $key ] = $value;
	}

	/**
	 * Unset variables from a faked HTTP POST request.
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Name of the POST variable.
	 */
	public function unset_post( $key ) {
		unset( $_POST[ $key ], $_REQUEST[ $key ] );
	}

} // class TablePress_TestCase
