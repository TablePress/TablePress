<?php
/**
 * Admin Page Helper Class for TablePress with functions needed in the admin area
 *
 * @package TablePress
 * @subpackage Admin Page Helper
 * @author Tobias Bäthge
 * @since 1.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin Page class
 */
class TablePress_Admin_Page {

	public function enqueue_style( $name ) {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.dev' : '';
		$css_file = "admin/{$name}{$suffix}.css";
		$css_url = plugins_url( $css_file, TABLEPRESS__FILE__ );
		wp_enqueue_style( "tablepress-{$name}", $css_url, array(), TablePress::version );
	}

	public function enqueue_script( $name, $dependencies, $localize_script = false ) {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.dev' : '';
		$js_file = "admin/{$name}{$suffix}.js";
		$js_url = plugins_url( $js_file, TABLEPRESS__FILE__ );
		wp_enqueue_script( "tablepress-{$name}", $js_url, $dependencies, TablePress::version, true );
		if ( $localize_script )
			wp_localize_script( "tablepress-{$name}", "tablepress_{$name}", $localize_script );
	}

	public function add_admin_footer_text() {
		// show admin footer message (only on pages of TablePress)
		add_filter( 'admin_footer_text', array( &$this, '_admin_footer_text' ) );
	}

	public function _admin_footer_text( $content ) {
		$content .= ' &bull; ' . __( 'Thank you for using <a href="http://tobias.baethge.com/wordpress/plugins/tablepress/">TablePress</a>.', 'tablepress' );
		$content .= ' ' . sprintf( __( 'Support the plugin with your <a href="%s">donation</a>!', 'tablepress' ), 'http://tobias.baethge.com/donate-message/' );
		return $content;
	}

} // class TablePress_Admin_Page