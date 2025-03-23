<?php
/**
 * Constants to be ignored in PHPStan checks.
 *
 * @see https://phpstan.org/user-guide/discovering-symbols
 */

namespace {

	// WordPress constants.
	define( 'WP_MEMORY_LIMIT', '1024M' );
	define( 'WPINC', 'wp-includes' );
	define( 'WP_START_TIMESTAMP', microtime( true ) );
	define( 'PCLZIP_OPT_EXTRACT_AS_STRING', 77006 );

	// TablePress constants.
	define( 'TABLEPRESS_ABSPATH', dirname( __DIR__ ) . '/' );
	define( 'TABLEPRESS_BASENAME', 'tablepress/tablepress.php' );

	// WordPress functions that are missing in phpstan-wordpress.
	function wxr_cdata( $str ) {
		return $str;
	}

	// Functions from external plugins.
	function w3tc_minify_flush() {}
	function WP_Optimize() {}

} // global namespace.

// Elementor classes.
namespace Elementor {

	class Controls_Manager {}

	class Widget_Base {}

	class Widgets_Manager {}

} // Elementor namespace.
