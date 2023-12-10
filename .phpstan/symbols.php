<?php
/**
 * Constants to be ignored in PHPStan checks.
 *
 * @see: https://phpstan.org/user-guide/discovering-symbols
 */

// WordPress constants.
define( 'WP_CONTENT_DIR', dirname( __FILE__, 3) );
define( 'WP_MEMORY_LIMIT', '1024M' );
define( 'WP_POST_REVISIONS', 3 );
define( 'WPINC', 'wp-includes' );
define( 'WP_START_TIMESTAMP', microtime( true ) );

// TablePress constants.
define( 'TABLEPRESS_ABSPATH', dirname( __DIR__ ) . '/' );
define( 'TABLEPRESS_BASENAME', 'tablepress/tablepress.php' );
