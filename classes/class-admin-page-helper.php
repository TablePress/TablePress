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
	 * @param string   $name         Name of the CSS file, without extension.
	 * @param string[] $dependencies Optional. List of names of CSS stylesheets that this stylesheet depends on, and which need to be included before this one.
	 */
	public function enqueue_style( string $name, array $dependencies = array() ): void {
		$css_file = "admin/css/build/{$name}.css";
		$css_url = plugins_url( $css_file, TABLEPRESS__FILE__ );
		wp_enqueue_style( "tablepress-{$name}", $css_url, $dependencies, TablePress::version );
	}

	/**
	 * Enqueue a JavaScript file, possibly with dependencies and extra information.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $name         Name of the JS file, without extension.
	 * @param string[]             $dependencies Optional. List of names of JS scripts that this script depends on, and which need to be included before this one.
	 * @param array<string, mixed> $script_data  Optional. JS data that is printed to the page before the script is included. The array key will be used as the name, the value will be JSON encoded.
	 */
	public function enqueue_script( string $name, array $dependencies = array(), array $script_data = array() ): void {
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

		/*
		 * Register the `react-jsx-runtime` polyfill, if it is not already registered.
		 * This is needed as a polyfill for WP < 6.6, and can be removed once WP 6.6 is the minimum requirement for TablePress.
		 */
		if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
			wp_register_script( 'react-jsx-runtime', plugins_url( 'admin/js/react-jsx-runtime.min.js', TABLEPRESS__FILE__ ), array( 'react' ), TablePress::version, true );
		}

		/**
		 * Filters the dependencies of a TablePress script file.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $dependencies List of the dependencies that the $name script relies on.
		 * @param string   $name         Name of the JS script, without extension.
		 */
		$dependencies = apply_filters( 'tablepress_admin_page_script_dependencies', $dependencies, $name );

		wp_enqueue_script( "tablepress-{$name}", $js_url, $dependencies, $version, true );

		// Load JavaScript translation files, for all scripts that rely on `wp-i18n`.
		if ( in_array( 'wp-i18n', $dependencies, true ) ) {
			wp_set_script_translations( "tablepress-{$name}", 'tablepress' );
		}

		if ( ! empty( $script_data ) ) {
			foreach ( $script_data as $var_name => $var_data ) {
				$var_data = wp_json_encode( $var_data, JSON_FORCE_OBJECT | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
				wp_add_inline_script( "tablepress-{$name}", "const tablepress_{$var_name} = {$var_data};", 'before' );
			}
		}
	}

	/**
	 * Register a filter hook on the admin footer.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_footer_text(): void {
		// Show admin footer message (only on TablePress admin screens).
		add_filter( 'admin_footer_text', array( $this, '_admin_footer_text' ) );
	}

	/**
	 * Adds a TablePress "Thank You" message to the admin footer content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Current admin footer content.
	 * @return string New admin footer content.
	 */
	public function _admin_footer_text( /* string */ $content ): string {
		// Don't use a type hint in the method declaration as many WordPress plugins use the `admin_footer_text` filter in the wrong way.

		// Protect against other plugins not returning a string in their filter callbacks.
		if ( ! is_string( $content ) ) { // @phpstan-ignore function.alreadyNarrowedType (The `is_string()` check is needed as the input is coming from a filter hook.)
			$content = '';
		}

		$content .= ' &bull; ' . sprintf( __( 'Thank you for using <a href="%s">TablePress</a>.', 'tablepress' ), 'https://tablepress.org/' );
		if ( tb_tp_fs()->is_free_plan() ) {
			$content .= ' ' . sprintf( __( 'Take a look at the <a href="%s">Premium features</a>!', 'tablepress' ), 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=admin-footer' );
		}
		return $content;
	}

	/**
	 * Print the JavaScript code for a WP feature pointer.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $pointer_id The pointer ID.
	 * @param string               $selector   The HTML elements, on which the pointer should be attached.
	 * @param array<string, mixed> $args       Arguments to be passed to the pointer JS (see wp-pointer.js).
	 */
	public function print_wp_pointer_js( string $pointer_id, string $selector, array $args ): void {
		if ( empty( $pointer_id ) || empty( $selector ) || empty( $args['content'] ) ) {
			return;
		}

		/*
		 * Print JS code for the feature pointers, extended with event handling for opened/closed "Screen Options", so that pointers can
		 * be repositioned. 210 ms is slightly slower than jQuery's "fast" value, to allow all elements to reach their original position.
		 */
		?>
<script>
( function( $ ) {
	let options = <?php echo wp_json_encode( $args, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ); ?>;

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

} // class TablePress_Admin_Page
