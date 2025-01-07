<?php
/**
 * Freemius plugin filters for TablePress.
 *
 * Note: This file must not contain PHP code that does not run on PHP < 7.4!
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @since 3.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

// Load the TablePress plugin icon for the Freemius opt-in/activation screen.
tb_tp_fs()->add_filter(
	'plugin_icon',
	static function ( $icon ) /* No return type declaration or type hints, due to required PHP compatibility of this file! */ {
		$icon = dirname( __DIR__ ) . '/admin/img/tablepress-icon.svg';
		return $icon;
	} // No trailing comma in function call, due to required PHP compatibility of this file!
);

// Load the TablePress style customizations for the Freemius Pricing screen.
tb_tp_fs()->add_filter(
	'pricing/css_path',
	static function ( $path ) /* No return type declaration or type hints, due to required PHP compatibility of this file! */ {
		$path = dirname( __DIR__ ) . '/admin/css/build/freemius-pricing.css';
		return $path;
	} // No trailing comma in function call, due to required PHP compatibility of this file!
);

// Hide the "Pricing" menu entry for users with a valid and active premium license.
tb_tp_fs()->add_filter(
	'is_submenu_visible',
	static function ( $is_visible, $menu_id ) /* No return type declaration or type hints, due to required PHP compatibility of this file! */ {
		if ( 'pricing' === $menu_id ) {
			$is_visible = ! TABLEPRESS_IS_PLAYGROUND_PREVIEW && ! tb_tp_fs()->is_paying_or_trial() && current_user_can( 'tablepress_list_tables' );
		}
		return $is_visible;
	},
	10,
	2 // No trailing comma in function call, due to required PHP compatibility of this file!
);

// Show only annual but not calculated monthly prices on the "Pricing" page.
tb_tp_fs()->add_filter( 'pricing/show_annual_in_monthly', '__return_false' );

// Determine the default currency based on the top-level domain (TLD) of the site URL.
tb_tp_fs()->add_filter(
	'default_currency',
	static function ( $currency ) /* No return type declaration or type hints, due to required PHP compatibility of this file! */ {
		$host = wp_parse_url( site_url(), PHP_URL_HOST );
		if ( is_string( $host ) ) {
			$tld = strtolower( pathinfo( $host, PATHINFO_EXTENSION ) );
			if ( in_array( $tld, array( 'at', 'be', 'bg', 'cy', 'cz', 'de', 'dk', 'ee', 'es', 'fi', 'fr', 'gr', 'hu', 'ie', 'it', 'lt', 'lu', 'lv', 'mt', 'nl', 'pl', 'pt', 'ro', 'se', 'si', 'sk', 'uk' ), true ) ) {
				$currency = 'eur';
			}
		}
		return $currency;
	} // No trailing comma in function call, due to required PHP compatibility of this file!
);

// Hide the tabs navigation on Freemius screens.
tb_tp_fs()->add_filter( 'hide_account_tabs', '__return_true' );

// Use different arrow icons in the admin menu.
tb_tp_fs()->override_i18n( array(
	'symbol_arrow-left'  => '&larr;',
	'symbol_arrow-right' => '&rarr;',
) );
