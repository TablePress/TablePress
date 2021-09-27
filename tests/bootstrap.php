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
	$wp_tests_dir = '../../../../../tests/phpunit/';
}

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/' );

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( 'tablepress/tablepress.php' ),
);

require_once $wp_tests_dir . 'includes/functions.php';

// Activates TablePress in WordPress so it can be tested.
function tablepress_tests_init() {
	require dirname( __DIR__ ) . '/tablepress.php';
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
	// Intentionally left blank.
} // class TablePress_TestCase
