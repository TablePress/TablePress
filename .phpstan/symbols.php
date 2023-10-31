<?php
/**
 * Classes and constants to be ignored in PHPStan checks.
 *
 * @see: https://phpstan.org/user-guide/discovering-symbols
 */

// WordPress constants.
define( 'WP_CONTENT_DIR', dirname( __FILE__, 3) );
define( 'WP_MEMORY_LIMIT', '1024M' );
define( 'WP_POST_REVISIONS', 3 );
define( 'WPINC', 'wp-includes' );

// TablePress constants.
define( 'TABLEPRESS_ABSPATH', dirname( __DIR__ ) . '/' );
define( 'TABLEPRESS_BASENAME', 'tablepress/tablepress.php' );

// Classes.
require_once TABLEPRESS_ABSPATH . 'classes/class-controller.php';
require_once TABLEPRESS_ABSPATH . 'controllers/controller-frontend.php';
class_alias( 'TablePress_Frontend_Controller', 'TablePress_' ); // PHPStan is looking for a "TablePress_" class for some reason.
