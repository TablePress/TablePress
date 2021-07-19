<?php
/**
 * WordPress plugin "TablePress" main file, responsible for initiating the plugin
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @version 1.14
 *
 *
 * Plugin Name: TablePress
 * Plugin URI: https://tablepress.org/
 * Description: Embed beautiful and feature-rich tables into your posts and pages, without having to write code.
 * Version: 1.14
 * Requires at least: 5.6
 * Requires PHP: 5.6.20
 * Author: Tobias Bäthge
 * Author URI: https://tobias.baethge.com/
 * Author email: wordpress@tobias.baethge.com
 * License: GPL 2
 * Donate URI: https://tablepress.org/donate/
 *
 *
 * Copyright 2012-2021 Tobias Bäthge
 *
 * TablePress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as published by
 * the Free Software Foundation.
 *
 * TablePress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WordPress. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

// Define certain plugin variables as constants.
if ( ! defined( 'TABLEPRESS_ABSPATH' ) ) {
	define( 'TABLEPRESS_ABSPATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'TABLEPRESS__FILE__' ) ) {
	define( 'TABLEPRESS__FILE__', __FILE__ );
}

if ( ! defined( 'TABLEPRESS__DIR__' ) ) {
	define( 'TABLEPRESS__DIR__', __DIR__ );
}

if ( ! defined( 'TABLEPRESS_BASENAME' ) ) {
	define( 'TABLEPRESS_BASENAME', plugin_basename( TABLEPRESS__FILE__ ) );
}

/*
 * Define global JSON encoding options that TablePress uses.
 */
if ( ! defined( 'TABLEPRESS_JSON_OPTIONS' ) ) {
	$tablepress_json_options = 0;
	$tablepress_json_options |= JSON_UNESCAPED_SLASHES; // Don't escape slashes to make search/replace of URLs in the database much easier.
	define( 'TABLEPRESS_JSON_OPTIONS', $tablepress_json_options );
	unset( $tablepress_json_options );
}

/**
 * Load TablePress class, which holds common functions and variables.
 */
require_once TABLEPRESS_ABSPATH . 'classes/class-tablepress.php';

// Start up TablePress on WordPress's "init" action hook.
add_action( 'init', array( 'TablePress', 'run' ) );
