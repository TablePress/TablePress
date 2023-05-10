<?php
/**
 * WordPress plugin "TablePress" main file, responsible for initiating the plugin.
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @version 2.1.3
 *
 *
 * Plugin Name: TablePress
 * Plugin URI: https://tablepress.org/
 * Description: Embed beautiful and feature-rich tables into your posts and pages, without having to write code.
 * Version: 2.1.3
 * Requires at least: 5.8
 * Requires PHP: 5.6.20
 * Author: Tobias Bäthge
 * Author URI: https://tablepress.org/
 * Author email: wordpress@tobias.baethge.com
 * License: GPL 2
 * Donate URI: https://tablepress.org/donate/
 *
 *
 * Copyright 2012-2023 Tobias Bäthge
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

if ( function_exists( 'tb_tp_fs' ) ) {
	tb_tp_fs()->set_basename( false, __FILE__ );
} else {
	if ( ! function_exists( 'tb_tp_fs' ) ) {
		/**
		 * Helper function for easier Freemius SDK access.
		 *
		 * @since 2.0.0
		 *
		 * @return Freemius Freemius SDK instance.
		 */
		function tb_tp_fs() {
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
						'pricing' => false,
					),
					'opt_in_moderation' => array(
						'new'       => true,
						'updates'   => false,
						'localhost' => false,
					),
					'is_live'           => true,
				) );
			}

			return $tb_tp_fs;
		}

		// Init Freemius.
		tb_tp_fs();

		// Load the TablePress plugin icon for the Freemius opt-in/activation screen.
		tb_tp_fs()->add_filter(
			'plugin_icon',
			static function() {
				return __DIR__ . '/admin/img/tablepress.png';
			}
		);

		// Hide the tabs navigation on Freemius screens.
		tb_tp_fs()->add_filter( 'hide_account_tabs', '__return_true' );

		// Hide the Powered by Freemius tab from generated pages, like "Upgrade" or "Pricing".
		tb_tp_fs()->add_filter( 'hide_freemius_powered_by', '__return_true' );

		// Use different arrow icons in the admin menu.
		tb_tp_fs()->override_i18n( array(
			'symbol_arrow-left'  => '&larr;',
			'symbol_arrow-right' => '&rarr;',
		) );

		// Signal that SDK was initiated.
		do_action( 'tb_tp_fs_loaded' );
	}

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
	 * Load TablePress class, which holds common functions and variables.
	 */
	require_once TABLEPRESS_ABSPATH . 'classes/class-tablepress.php';

	/*
	 * Start up TablePress on WordPress's "init" action hook.
	 */
	add_action( 'init', array( 'TablePress', 'run' ) );
}
