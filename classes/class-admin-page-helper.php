<?php
/**
 * Admin Page Helper Class for TablePress with functions needed in the admin area
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin Page class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Admin_Page {

	/**
	 * Enqueue a CSS file, possibly with dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name         Name of the CSS file, without extension.
	 * @param array  $dependencies Optional. List of names of CSS stylesheets that this stylesheet depends on, and which need to be included before this one.
	 */
	public function enqueue_style( $name, array $dependencies = array() ) {
		$css_file = "admin/css/build/{$name}.css";
		$css_url = plugins_url( $css_file, TABLEPRESS__FILE__ );
		wp_enqueue_style( "tablepress-{$name}", $css_url, $dependencies, TablePress::version );
	}

	/**
	 * Enqueue a JavaScript file, possibly with dependencies and extra information.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name         Name of the JS file, without extension.
	 * @param array  $dependencies Optional. List of names of JS scripts that this script depends on, and which need to be included before this one.
	 * @param array  $script_data  Optional. JS data that is printed to the page before the script is included. The array key will be used as the name, the value will be JSON encoded.
	 */
	public function enqueue_script( $name, array $dependencies = array(), array $script_data = array() ) {
		$js_file = "admin/js/build/{$name}.js";
		$js_url = plugins_url( $js_file, TABLEPRESS__FILE__ );

		$version = TablePress::version;

		// Load dependencies and version from the auto-generated asset PHP file.
		$script_asset_path = TABLEPRESS_ABSPATH . "admin/js/build/{$name}.asset.php";
		if ( file_exists( $script_asset_path ) ) {
			$script_asset = require $script_asset_path;
			if ( isset( $script_asset['dependencies'] ) ) {
				$dependencies = array_merge( $dependencies, $script_asset['dependencies'] );
			}
			if ( isset( $script_asset['version'] ) ) {
				$version = $script_asset['version'];
			}
		}

		/**
		 * Filters the dependencies of a TablePress script file.
		 *
		 * @since 2.0.0
		 *
		 * @param array  $dependencies List of the dependencies that the $name script relies on.
		 * @param string $name         Name of the JS script, without extension.
		 */
		$dependencies = apply_filters( 'tablepress_admin_page_script_dependencies', $dependencies, $name );

		wp_enqueue_script( "tablepress-{$name}", $js_url, $dependencies, $version, true );

		// Load JavaScript translation files, for all scripts that rely on `wp-i18n`.
		if ( in_array( 'wp-i18n', $dependencies, true ) ) {
			wp_set_script_translations( "tablepress-{$name}", 'tablepress' );
		}

		if ( ! empty( $script_data ) ) {
			foreach ( $script_data as $var_name => $var_data ) {
				$var_data = wp_json_encode( $var_data, JSON_FORCE_OBJECT );
				wp_add_inline_script( "tablepress-{$name}", "const tablepress_{$var_name} = {$var_data};", 'before' );
			}
		}
	}

	/**
	 * Register a filter hook on the admin footer.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_footer_text() {
		// Show admin footer message (only on TablePress admin screens).
		add_filter( 'admin_footer_text', array( $this, '_admin_footer_text' ) );
	}

	/**
	 * Add a TablePress "Thank You" message to the admin footer content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Current admin footer content.
	 * @return string New admin footer content.
	 */
	public function _admin_footer_text( $content ) {
		$content .= ' &bull; ' . sprintf( __( 'Thank you for using <a href="%s">TablePress</a>.', 'tablepress' ), 'https://tablepress.org/' );
		if ( tb_tp_fs()->is_free_plan() ) {
			$content .= ' ' . sprintf( __( 'Take a look at the <a href="%s">Premium features</a>!', 'tablepress' ), 'https://tablepress.org/premium/' );
		}
		return $content;
	}

	/**
	 * Print the JavaScript code for a WP feature pointer.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pointer_id The pointer ID.
	 * @param string $selector   The HTML elements, on which the pointer should be attached.
	 * @param array  $args       Arguments to be passed to the pointer JS (see wp-pointer.js).
	 */
	public function print_wp_pointer_js( $pointer_id, $selector, array $args ) {
		if ( empty( $pointer_id ) || empty( $selector ) || empty( $args['content'] ) ) {
			return;
		}

		/*
		 * Print JS code for the feature pointers, extened with event handling for opened/closed "Screen Options", so that pointers can
		 * be repositioned. 210 ms is slightly slower than jQuery's "fast" value, to allow all elements to reach their original position.
		 */
		?>
<script>
( function( $ ) {
	let options = <?php echo wp_json_encode( $args, TABLEPRESS_JSON_OPTIONS ); ?>;

	if ( ! options ) {
		return;
	}

	options = $.extend( options, {
		close: function() {
			$.post( ajaxurl, {
				pointer: '<?php echo $pointer_id; ?>',
				action: 'dismiss-wp-pointer'
			} );
			$( this ).pointer( { 'disabled': true } );
		}
	} );

	$( function () {
		$( '<?php echo $selector; ?>' ).pointer( options ).pointer( 'open' );
	} );

	$( document ).on( 'screen:options:open screen:options:close', function () {
		setTimeout( function () { $( '<?php echo $selector; ?>' ).pointer( 'reposition' ); }, 210 );
	} );
} )( jQuery );
</script>
		<?php
	}

	/**
	 * Returns a safe JSON representation of a variable for printing inside of JavaScript code.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $data Variable to convert to JSON.
	 * @return string Safe JSON representation of a variable for printing inside of JavaScript code.
	 */
	public function convert_to_json_parse_output( $data ) {
		$json = wp_json_encode( $data, TABLEPRESS_JSON_OPTIONS );
		// Print them inside a `JSON.parse()` call in JS for speed gains, with necessary escaping of `</script>`, `'`, and `\`.
		$json = str_replace( array( '</script>', '\\', "'" ), array( '<\/script>', '\\\\', "\'" ), $json );
		return "JSON.parse( '{$json}' )";
	}

} // class TablePress_Admin_Page
