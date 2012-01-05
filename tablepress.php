<?php
/**
 * WordPress plugin "TablePress" main file, responsible for initiating the plugin
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @version 1.0
 */

/*
Plugin Name: TablePress
Plugin URI: http://tobias.baethge.com/wordpress/plugins/tablepress/
Description: ...
Version: 1.0-alpha
Author: Tobias Bäthge
Author URI: http://tobias.baethge.com/
Author eMail: wordpress@tobias.baethge.com
Text Domain: tablepress
Domain Path: /i18n
License: GPL 2
Donate URI: http://tobias.baethge.com/donate/
*/

/*	Copyright 2012 Tobias Bäthge

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Check whether TablePress is already loaded somehow
if ( defined( 'TABLEPRESS_ABSPATH' ) ) {
	// do self-deactivation here
	die( 'TablePress loaded twice!' );
}

// Define certain plugin variables as constants
define( 'TABLEPRESS_ABSPATH', plugin_dir_path( __FILE__ ) );
define( 'TABLEPRESS__FILE__', __FILE__ );

// Load TablePress class, which holds common functions and variables
require_once ( TABLEPRESS_ABSPATH . 'classes/class-tablepress.php' );

// Start up TablePress on init, which loads the necessary controller
//TablePress::run();
add_action( 'init', array( 'TablePress', 'run' ) );