<?php
/**
 * WordPress plugin "TablePress" main file, responsible for initiating the plugin.
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @version 3.2.5
 *
 *
 * Plugin Name: TablePress
 * Plugin URI: https://tablepress.org/
 * Description: Embed beautiful and interactive tables into your WordPress website’s posts and pages, without having to write code!
 * Version: 3.2.5
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Tobias Bäthge
 * Author URI: https://tablepress.org/
 * Author email: wordpress@tobias.baethge.com
 * License: GPL 2
 * Donate URI: https://tablepress.org/donate/
 *
 *
 * Copyright 2012-2025 Tobias Bäthge
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
 *
 * Note: This file must not contain PHP code that does not run on PHP < 7.4!
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

if ( ! defined( 'TABLEPRESS_IS_PLAYGROUND_PREVIEW' ) ) {
	define( 'TABLEPRESS_IS_PLAYGROUND_PREVIEW', false );
}

if ( function_exists( 'tb_tp_fs' ) ) {
	tb_tp_fs()->set_basename( false, __FILE__ ); // @phpstan-ignore argument.type (Wrong variable type in Freemius function docblock.)
} else {
	/**
	 * Helper function for easier Freemius SDK access.
	 *
	 * @since 2.0.0
	 *
	 * @return Freemius Freemius SDK instance.
	 */
	function tb_tp_fs() /* No return type declaration, due to required PHP compatibility of this file! */ {
		global $tb_tp_fs;

		if ( ! isset( $tb_tp_fs ) ) {
			// Include Freemius SDK.
			require_once __DIR__ . '/libraries/freemius/start.php';

			$tb_tp_fs = fs_dynamic_init( array(
				'id'                => '10340',
				'slug'              => 'tablepress',
				'type'              => 'plugin',
				'public_key'        => 'pk_b215ca1bb4041cf43ed137ae7665b',
				'is_premium'        => false,
				'has_addons'        => false,
				'has_paid_plans'    => true,
				'menu'              => array(
					'slug'    => 'tablepress',
					'contact' => false,
					'support' => false,
					'account' => false,
				),
				'opt_in_moderation' => array(
					'new'       => true,
					'updates'   => false,
					'localhost' => false,
				),
				'is_live'           => true,
				'anonymous_mode'    => TABLEPRESS_IS_PLAYGROUND_PREVIEW,
			) );
		}

		return $tb_tp_fs;
	}

	// Init Freemius.
	tb_tp_fs();

	// Register Freemius plugin filter hooks.
	require_once __DIR__ . '/controllers/freemius-filters.php';

	// Signal that the SDK was initiated.
	do_action( 'tb_tp_fs_loaded' );

	/*
	 * Define certain plugin variables as constants.
	 */
	if ( ! defined( 'TABLEPRESS_ABSPATH' ) ) {
		define( 'TABLEPRESS_ABSPATH', trailingslashit( __DIR__ ) );
	}
	if ( ! defined( 'TABLEPRESS__FILE__' ) ) {
		define( 'TABLEPRESS__FILE__', __FILE__ );
	}
	if ( ! defined( 'TABLEPRESS_BASENAME' ) ) {
		define( 'TABLEPRESS_BASENAME', plugin_basename( TABLEPRESS__FILE__ ) );
	}
	if ( ! defined( 'TABLEPRESS_JSON_OPTIONS' ) ) {
		// JSON_UNESCAPED_SLASHES: Don't escape slashes, e.g. to make search/replace of URLs in the database easier.
		define( 'TABLEPRESS_JSON_OPTIONS', JSON_UNESCAPED_SLASHES );
	}

	/*
	 * Check if the site environment fulfills the minimum requirements.
	 */
	if ( ! require_once TABLEPRESS_ABSPATH . 'controllers/environment-checks.php' ) {
		return; // Exit early if the return value from the file is false.
	}

	/*
	 * Load TablePress class, which holds common functions and variables.
	 */
	require_once TABLEPRESS_ABSPATH . 'classes/class-tablepress.php';

	/*
	 * Start up TablePress on WordPress's "init" action hook.
	 */
	add_action( 'init', array( 'TablePress', 'run' ) );
}
