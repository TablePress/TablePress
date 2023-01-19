<?php
/**
 * Frontend Controller for TablePress with functionality for the frontend
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Frontend Controller class, extends Base Controller Class
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Frontend_Controller extends TablePress_Controller {

	/**
	 * List of tables that are shown for the current request.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $shown_tables = array();

	/**
	 * Initiate Frontend functionality.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * Filters whether the TablePress Default CSS code shall be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $use Whether the Default CSS shall be loaded. Default true.
		 */
		if ( apply_filters( 'tablepress_use_default_css', true ) || TablePress::$model_options->get( 'use_custom_css' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css' ) );
		}

		// Add DataTables invocation calls.
		add_action( 'wp_print_footer_scripts', array( $this, 'add_datatables_calls' ), 11 ); // After inclusion of files.

		// Register TablePress Shortcodes. Priority 20 is kept for backwards-compatibility purposes.
		add_action( 'init', array( $this, 'init_shortcodes' ), 20 );

		/**
		 * Filters whether the WordPress search shall also search TablePress tables.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $search Whether the TablePress tables shall be searched. Default true.
		 */
		if ( apply_filters( 'tablepress_wp_search_integration', true ) ) {
			// Extend WordPress Search to also find posts/pages that have a table with the one of the search terms in title (if shown), description (if shown), or content.
			add_filter( 'posts_search', array( $this, 'posts_search_filter' ) );
		}

		/**
		 * Load TablePress Template Tag functions.
		 */
		TablePress::load_file( 'template-tag-functions.php', 'controllers' );

		/**
		 * Register the tablepress/table block and its dependencies.
		 */
		register_block_type(
			TABLEPRESS_ABSPATH . 'blocks/table/',
			array(
				'render_callback' => array( $this, 'table_block_render_callback' ),
			)
		);
	}

	/**
	 * Register TablePress Shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function init_shortcodes() {
		add_shortcode( TablePress::$shortcode, array( $this, 'shortcode_table' ) );
		add_shortcode( TablePress::$shortcode_info, array( $this, 'shortcode_table_info' ) );
	}

	/**
	 * Enqueue CSS files for default CSS and "Custom CSS" (if desired).
	 *
	 * @since 1.0.0
	 */
	public function enqueue_css() {
		/** This filter is documented in controllers/controller-frontend.php */
		$use_default_css = apply_filters( 'tablepress_use_default_css', true );
		$custom_css = TablePress::$model_options->get( 'custom_css' );
		$use_custom_css = ( TablePress::$model_options->get( 'use_custom_css' ) && '' !== $custom_css );
		$use_custom_css_file = ( $use_custom_css && TablePress::$model_options->get( 'use_custom_css_file' ) );
		/**
		 * Filters the "Custom CSS" version number that is appended to the enqueued CSS files
		 *
		 * @since 1.0.0
		 *
		 * @param int $version The "Custom CSS" version.
		 */
		$custom_css_version = apply_filters( 'tablepress_custom_css_version', TablePress::$model_options->get( 'custom_css_version' ) );

		$tablepress_css = TablePress::load_class( 'TablePress_CSS', 'class-css.php', 'classes' );

		// Determine Default CSS URL.
		$rtl = ( is_rtl() ) ? '-rtl' : '';
		$unfiltered_default_css_url = plugins_url( "css/build/default{$rtl}.css", TABLEPRESS__FILE__ );
		/**
		 * Filters the URL from which the TablePress Default CSS file is loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $unfiltered_default_css_url URL of the TablePress Default CSS file.
		 */
		$default_css_url = apply_filters( 'tablepress_default_css_url', $unfiltered_default_css_url );

		$use_custom_css_combined_file = ( $use_default_css && $use_custom_css_file && ! SCRIPT_DEBUG && ! is_rtl() && $unfiltered_default_css_url === $default_css_url && $tablepress_css->load_custom_css_from_file( 'combined' ) );

		if ( $use_custom_css_combined_file ) {
			$custom_css_combined_url = $tablepress_css->get_custom_css_location( 'combined', 'url' );
			// Need to use 'tablepress-default' instead of 'tablepress-combined' to not break existing TablePress Extensions.
			wp_enqueue_style( 'tablepress-default', $custom_css_combined_url, array(), $custom_css_version );
		} else {
			$custom_css_dependencies = array();
			if ( $use_default_css ) {
				wp_enqueue_style( 'tablepress-default', $default_css_url, array(), TablePress::version );
				// Add dependency to make sure that Custom CSS is printed after Default CSS.
				$custom_css_dependencies[] = 'tablepress-default';
			}

			$use_custom_css_minified_file = ( $use_custom_css_file && ! SCRIPT_DEBUG && $tablepress_css->load_custom_css_from_file( 'minified' ) );
			if ( $use_custom_css_minified_file ) {
				$custom_css_minified_url = $tablepress_css->get_custom_css_location( 'minified', 'url' );
				wp_enqueue_style( 'tablepress-custom', $custom_css_minified_url, $custom_css_dependencies, $custom_css_version );
				return;
			}

			$use_custom_css_normal_file = ( $use_custom_css_file && $tablepress_css->load_custom_css_from_file( 'normal' ) );
			if ( $use_custom_css_normal_file ) {
				$custom_css_normal_url = $tablepress_css->get_custom_css_location( 'normal', 'url' );
				wp_enqueue_style( 'tablepress-custom', $custom_css_normal_url, $custom_css_dependencies, $custom_css_version );
				return;
			}

			if ( $use_custom_css ) {
				// Get "Custom CSS" from options, try minified Custom CSS first.
				$custom_css_minified = TablePress::$model_options->get( 'custom_css_minified' );
				if ( ! empty( $custom_css_minified ) ) {
					$custom_css = $custom_css_minified;
				}
				/**
				 * Filters the "Custom CSS" code that is to be loaded as inline CSS.
				 *
				 * @since 1.0.0
				 *
				 * @param string $custom_css The "Custom CSS" code.
				 */
				$custom_css = apply_filters( 'tablepress_custom_css', $custom_css );
				if ( ! empty( $custom_css ) ) {
					// wp_add_inline_style() requires a loaded CSS file, so we have to work around that if "Default CSS" is disabled.
					if ( $use_default_css ) {
						// Handle of the file to which the <style> shall be appended.
						wp_add_inline_style( 'tablepress-default', $custom_css );
					} else {
						add_action( 'wp_head', array( $this, '_print_custom_css' ), 8 ); // Priority 8 to hook in right after WP_Styles has been processed.
					}
				}
			}
		}
	}

	/**
	 * Print "Custom CSS" to "wp_head" inline.
	 *
	 * This is necessary if "Default CSS" is off, and saving "Custom CSS" to a file is not possible.
	 *
	 * @since 1.0.0
	 */
	public function _print_custom_css() {
		// Get "Custom CSS" from options, try minified Custom CSS first.
		$custom_css = TablePress::$model_options->get( 'custom_css_minified' );
		if ( empty( $custom_css ) ) {
			$custom_css = TablePress::$model_options->get( 'custom_css' );
		}
		/** This filter is documented in controllers/controller-frontend.php */
		$custom_css = apply_filters( 'tablepress_custom_css', $custom_css );
		echo "<style>\n{$custom_css}\n</style>\n";
	}

	/**
	 * Enqueue the DataTables JavaScript library and its dependencies.
	 *
	 * @since 1.0.0
	 */
	protected function _enqueue_datatables() {
		$js_file = 'js/jquery.datatables.min.js';
		$js_url = plugins_url( $js_file, TABLEPRESS__FILE__ );
		/**
		 * Filters the URL from which the DataTables JavaScript library file is loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $js_url  URL of the DataTables JS library file.
		 * @param string $js_file Path and file name of the DataTables JS library file.
		 */
		$js_url = apply_filters( 'tablepress_datatables_js_url', $js_url, $js_file );
		wp_enqueue_script( 'tablepress-datatables', $js_url, array( 'jquery-core' ), TablePress::version, true );
	}

	/**
	 * Add JS code for invocation of DataTables JS library.
	 *
	 * @since 1.0.0
	 */
	public function add_datatables_calls() {
		// Prevent repeated execution (which would lead to DataTables error messages) via a static variable.
		static $datatables_calls_printed = false;
		if ( $datatables_calls_printed ) {
			return;
		}

		if ( empty( $this->shown_tables ) ) {
			// There are no tables with activated DataTables on the page that is currently rendered.
			return;
		}

		// Storage for the DataTables language strings.
		$datatables_language = array();
		// Generate the specific JS commands, depending on chosen features on the "Edit" screen and the Shortcode parameters.
		$commands = array();

		foreach ( $this->shown_tables as $table_id => $table_store ) {
			if ( empty( $table_store['instances'] ) ) {
				continue;
			}

			foreach ( $table_store['instances'] as $html_id => $js_options ) {
				$parameters = array();

				// Settle dependencies/conflicts between certain features.
				if ( false !== $js_options['datatables_scrolly'] ) { // datatables_scrolly can be a string, so that the explicit `false` check is needed.
					// Vertical scrolling and pagination don't work together.
					$js_options['datatables_paginate'] = false;
				}
				// Sanitize, as it may come from a Shortcode attribute.
				$js_options['datatables_paginate_entries'] = (int) $js_options['datatables_paginate_entries'];

				/*
				 * DataTables language/translation handling.
				 */

				/**
				 * Filters the locale/language for the DataTables JavaScript library.
				 *
				 * @since 1.0.0
				 *
				 * @param string $locale   The DataTables JS library locale.
				 * @param string $table_id The current table ID.
				 */
				$datatables_locale = apply_filters( 'tablepress_datatables_locale', $js_options['datatables_locale'], $table_id );

				// Only load each locale's language file once.
				if ( ! isset( $datatables_language[ $datatables_locale ] ) ) {
					$orig_language_file = TABLEPRESS_ABSPATH . "i18n/datatables/lang-{$datatables_locale}.php";

					/**
					 * Filters the language file path for the DataTables JavaScript library.
					 *
					 * PHP files that return an array and JSON files are supported.
					 * The JSON file method is deprecated and should no longer be used.
					 *
					 * @since 1.0.0
					 *
					 * @param string $orig_language_file Language file path for the DataTables JS library.
					 * @param string $datatables_locale  Current locale/language for the DataTables JS library.
					 * @param string $tablepress_abspath Base path of the TablePress plugin.
					 */
					$language_file = apply_filters( 'tablepress_datatables_language_file', $orig_language_file, $datatables_locale, TABLEPRESS_ABSPATH );

					/*
					 * Load translation file if it's not "en_US" (included as the default in DataTables)
					 * or if the filter was used to change the language file, and the language file exists.
					 * Otherwise, use an empty en_US placeholder, so that the strings are filterable later.
					 */
					if ( ( 'en_US' !== $datatables_locale || $orig_language_file !== $language_file ) && file_exists( $language_file ) ) {
						if ( 0 === substr_compare( $language_file, '.php', -4, 4, false ) ) {
							$datatables_strings = require $language_file;
							if ( ! is_array( $datatables_strings ) ) {
								$datatables_strings = array();
							}
						} elseif ( 0 === substr_compare( $language_file, '.json', -5, 5, false ) ) {
							$datatables_strings = file_get_contents( $language_file );
							$datatables_strings = json_decode( $datatables_strings, true );
							// Check if JSON could be decoded.
							if ( is_null( $datatables_strings ) ) {
								$datatables_strings = array();
							}
							$datatables_strings = (array) $datatables_strings;
						} else {
							// The filtered language file exists, but is not a .php or .json file, so don't use it.
							$datatables_strings = array();
						}
					} else {
						// If no translation file for the defined locale exists or is needed, use "en_US", as that's built-in.
						$datatables_locale = 'en_US';
						$datatables_strings = array();
					}

					/**
					 * Filters the language strings for the DataTables JavaScript library's features.
					 *
					 * @since 2.0.0
					 *
					 * @param array  $datatables_strings The language strings for DataTables.
					 * @param string $datatables_locale  Current locale/language for the DataTables JS library.
					 */
					$datatables_language[ $datatables_locale ] = apply_filters( 'tablepress_datatables_language_strings', $datatables_strings, $datatables_locale );
				}
				$parameters['language'] = '"language":DT_language["' . $datatables_locale . '"]';

				// These parameters need to be added for performance gain or to overwrite unwanted default behavior.
				if ( $js_options['datatables_sort'] ) {
					// No initial sort.
					$parameters['order'] = '"order":[]';
					// Don't add additional classes, to speed up sorting.
					$parameters['orderClasses'] = '"orderClasses":false';
				}

				// Alternating row colors is default, so remove them if not wanted with [].
				$parameters['stripeClasses'] = '"stripeClasses":' . ( ( $js_options['alternating_row_colors'] ) ? '["even","odd"]' : '[]' );

				// The following options are activated by default, so we only need to "false" them if we don't want them, but don't need to "true" them if we do.
				if ( ! $js_options['datatables_sort'] ) {
					$parameters['ordering'] = '"ordering":false';
				}
				if ( $js_options['datatables_paginate'] ) {
					$parameters['pagingType'] = '"pagingType":"simple"';
					if ( $js_options['datatables_lengthchange'] ) {
						$length_menu = array( 10, 25, 50, 100 );
						if ( ! in_array( $js_options['datatables_paginate_entries'], $length_menu, true ) ) {
							$length_menu[] = $js_options['datatables_paginate_entries'];
							sort( $length_menu, SORT_NUMERIC );
							$parameters['lengthMenu'] = '"lengthMenu":[' . implode( ',', $length_menu ) . ']';
						}
					} else {
						$parameters['lengthChange'] = '"lengthChange":false';
					}
					if ( 10 !== $js_options['datatables_paginate_entries'] ) {
						$parameters['pageLength'] = '"pageLength":' . $js_options['datatables_paginate_entries'];
					}
				} else {
					$parameters['paging'] = '"paging":false';
				}
				if ( ! $js_options['datatables_filter'] ) {
					$parameters['searching'] = '"searching":false';
				}
				if ( ! $js_options['datatables_info'] ) {
					$parameters['info'] = '"info":false';
				}
				if ( $js_options['datatables_scrollx'] ) {
					$parameters['scrollX'] = '"scrollX":true';
				}
				if ( false !== $js_options['datatables_scrolly'] ) {
					$parameters['scrollY'] = '"scrollY":"' . preg_replace( '#[^0-9a-z.%]#', '', $js_options['datatables_scrolly'] ) . '"';
					$parameters['scrollCollapse'] = '"scrollCollapse":true';
				}
				if ( ! empty( $js_options['datatables_custom_commands'] ) ) {
					$parameters['custom_commands'] = $js_options['datatables_custom_commands'];
				}

				/**
				 * Filters the parameters that are passed to the DataTables JavaScript library.
				 *
				 * @since 1.0.0
				 *
				 * @param array  $parameters The parameters for the DataTables JS library.
				 * @param string $table_id   The current table ID.
				 * @param string $html_id    The ID of the table HTML element.
				 * @param array  $js_options The options for the JS library.
				 */
				$parameters = apply_filters( 'tablepress_datatables_parameters', $parameters, $table_id, $html_id, $js_options );

				// If an existing parameter (in the from `"parameter":`) is set in the "Custom Commands", remove its default value.
				if ( isset( $parameters['custom_commands'] ) ) {
					foreach ( array_keys( $parameters ) as $maybe_overwritten_parameter ) {
						if ( false !== strpos( $parameters['custom_commands'], "\"{$maybe_overwritten_parameter}\":" ) ) {
							unset( $parameters[ $maybe_overwritten_parameter ] );
						}
					}
				}

				$parameters = implode( ',', $parameters );
				$parameters = ( ! empty( $parameters ) ) ? '{' . $parameters . '}' : '';

				$command = "$('#{$html_id}').DataTable({$parameters});";
				/**
				 * Filters the JavaScript command that invokes the DataTables JavaScript library on one table.
				 *
				 * @since 1.0.0
				 *
				 * @param string $command    The JS command for the DataTables JS library.
				 * @param string $html_id    The ID of the table HTML element.
				 * @param string $parameters The parameters for the DataTables JS library.
				 * @param string $table_id   The current table ID.
				 * @param array  $js_options The options for the JS library.
				 */
				$command = apply_filters( 'tablepress_datatables_command', $command, $html_id, $parameters, $table_id, $js_options );
				if ( ! empty( $command ) ) {
					$commands[] = $command;
				}
			} // foreach table instance
		} // foreach table ID

		// DataTables language/translation handling.
		if ( ! empty( $datatables_language ) ) {
			$datatables_language = wp_json_encode( $datatables_language, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT );
			$datatables_language = "var DT_language={$datatables_language};\n";
		}

		$commands = implode( "\n", $commands );
		/**
		 * Filters the JavaScript commands that invoke the DataTables JavaScript library on all tables on the page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $commands The JS commands for the DataTables JS library.
		 */
		$commands = apply_filters( 'tablepress_all_datatables_commands', $commands );
		if ( empty( $commands ) ) {
			return;
		}

		$script_type_attr = current_theme_supports( 'html5', 'script' ) ? '' : ' type="text/javascript"';

		$js_wrapper = <<<'JS'
<script%3$s>
jQuery(function($){
%1$s%2$s
});
</script>
JS;
		/**
		 * Filters the script/jQuery wrapper code for the DataTables commands calls.
		 *
		 * @since 1.14.0
		 *
		 * @param string $js_wrapper Default script/jQuery wrapper code for the DataTables commands calls.
		 */
		$js_wrapper = apply_filters( 'tablepress_all_datatables_commands_wrapper', $js_wrapper );
		printf( $js_wrapper, $datatables_language, $commands, $script_type_attr );

		// Prevent repeated execution (which would lead to DataTables error messages) via a static variable.
		$datatables_calls_printed = true;
	}

	/**
	 * Handle Shortcode [table id=<ID> /].
	 *
	 * @since 1.0.0
	 *
	 * @param array $shortcode_atts List of attributes that where included in the Shortcode.
	 * @return string Resulting HTML code for the table with the ID <ID>.
	 */
	public function shortcode_table( $shortcode_atts ) {
		// Don't use `array` type hint in method declaration, as for empty Shortcodes like [table] or [table /], an empty string is passed, see WP Core #26927.
		$shortcode_atts = (array) $shortcode_atts;

		$_render = TablePress::load_class( 'TablePress_Render', 'class-render.php', 'classes' );

		$default_shortcode_atts = $_render->get_default_render_options();
		/**
		 * Filters the available/default attributes for the [table] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param array $default_shortcode_atts The [table] Shortcode default attributes.
		 */
		$default_shortcode_atts = apply_filters( 'tablepress_shortcode_table_default_shortcode_atts', $default_shortcode_atts );
		// Parse Shortcode attributes, only allow those that are specified.
		$shortcode_atts = shortcode_atts( $default_shortcode_atts, $shortcode_atts ); // Optional third argument left out on purpose. Use filter in the next line instead.
		/**
		 * Filters the attributes that were passed to the [table] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param array $shortcode_atts The attributes passed to the [table] Shortcode.
		 */
		$shortcode_atts = apply_filters( 'tablepress_shortcode_table_shortcode_atts', $shortcode_atts );

		// Check, if a table with the given ID exists.
		$table_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', $shortcode_atts['id'] );
		if ( ! TablePress::$model_table->table_exists( $table_id ) ) {
			$message = "[table &#8220;{$table_id}&#8221; not found /]<br />\n";
			/**
			 * Filters the "Table not found" message.
			 *
			 * @since 1.0.0
			 *
			 * @param string $message  The "Table not found" message.
			 * @param string $table_id The current table ID.
			 */
			$message = apply_filters( 'tablepress_table_not_found_message', $message, $table_id );
			return $message;
		}

		// Load table, with table data, options, and visibility settings.
		$table = TablePress::$model_table->load( $table_id, true, true );
		if ( is_wp_error( $table ) ) {
			$message = "[table &#8220;{$table_id}&#8221; could not be loaded /]<br />\n";
			/**
			 * Filters the "Table could not be loaded" message.
			 *
			 * @since 1.0.0
			 *
			 * @param string   $message  The "Table could not be loaded" message.
			 * @param string   $table_id The current table ID.
			 * @param WP_Error $table    The error object for the table.
			 */
			$message = apply_filters( 'tablepress_table_load_error_message', $message, $table_id, $table );
			return $message;
		}
		if ( isset( $table['is_corrupted'] ) && $table['is_corrupted'] ) {
			$message = "<div>Attention: The internal data of table &#8220;{$table_id}&#8221; is corrupted!</div>";
			/**
			 * Filters the "Table data is corrupted" message.
			 *
			 * @since 1.0.0
			 *
			 * @param string $message    The "Table data is corrupted" message.
			 * @param string $table_id   The current table ID.
			 * @param string $json_error The JSON error with information about the corrupted table.
			 */
			$message = apply_filters( 'tablepress_table_corrupted_message', $message, $table_id, $table['json_error'] );
			return $message;
		}

		/**
		 * Filters whether the "datatables_custom_commands" Shortcode parameter is disabled.
		 *
		 * By default, the "datatables_custom_commands" Shortcode parameter is disabled for security reasons.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $disable Whether to disable the "datatables_custom_commands" Shortcode parameter. Default true.
		 */
		if ( ! is_null( $shortcode_atts['datatables_custom_commands'] ) && apply_filters( 'tablepress_disable_custom_commands_shortcode_parameter', true ) ) {
			$shortcode_atts['datatables_custom_commands'] = null;
		}

		// Determine options to use (if set in Shortcode, use those, otherwise use stored options, from the "Edit" screen).
		$render_options = array();
		foreach ( $shortcode_atts as $key => $value ) {
			// We have to check this, because strings 'true' or 'false' are not recognized as boolean!
			if ( is_string( $value ) && 'true' === strtolower( $value ) ) {
				$render_options[ $key ] = true;
			} elseif ( is_string( $value ) && 'false' === strtolower( $value ) ) {
				$render_options[ $key ] = false;
			} elseif ( is_null( $value ) && isset( $table['options'][ $key ] ) ) {
				$render_options[ $key ] = $table['options'][ $key ];
			} else {
				$render_options[ $key ] = $value;
			}
		}

		// Generate unique HTML ID, depending on how often this table has already been shown on this page.
		if ( ! isset( $this->shown_tables[ $table_id ] ) ) {
			$this->shown_tables[ $table_id ] = array(
				'count'     => 0,
				'instances' => array(),
			);
		}
		++$this->shown_tables[ $table_id ]['count'];
		$count = $this->shown_tables[ $table_id ]['count'];
		$render_options['html_id'] = "tablepress-{$table_id}";
		if ( $count > 1 ) {
			$render_options['html_id'] .= "-no-{$count}";
		}
		/**
		 * Filters the ID of the table HTML element.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html_id  The ID of the table HTML element.
		 * @param string $table_id The current table ID.
		 * @param string $count    Number of copies of the table with this table ID on the page.
		 */
		$render_options['html_id'] = apply_filters( 'tablepress_html_id', $render_options['html_id'], $table_id, $count );

		// Generate the "Edit Table" link.
		$render_options['edit_table_url'] = '';
		/**
		 * Filters whether the "Edit" link below the table shall be shown.
		 *
		 * The "Edit" link is only shown to logged-in users who possess the necessary capability to edit the table.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $show     Whether to show the "Edit" link below the table. Default true.
		 * @param string $table_id The current table ID.
		 */
		if ( is_user_logged_in() && apply_filters( 'tablepress_edit_link_below_table', true, $table['id'] ) && current_user_can( 'tablepress_edit_table', $table['id'] ) ) {
			$render_options['edit_table_url'] = TablePress::url( array( 'action' => 'edit', 'table_id' => $table['id'] ) );
		}

		/**
		 * Filters the render options for the table.
		 *
		 * The render options are determined from the settings on a table's "Edit" screen and the Shortcode parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array $render_options The render options for the table.
		 * @param array $table          The current table.
		 */
		$render_options = apply_filters( 'tablepress_table_render_options', $render_options, $table );

		// Check if table output shall and can be loaded from the transient cache, otherwise generate the output.
		if ( $render_options['cache_table_output'] && ! is_user_logged_in() ) {
			// Hash the Render Options array to get a unique cache identifier.
			$table_hash = md5( wp_json_encode( $render_options, TABLEPRESS_JSON_OPTIONS ) );
			$transient_name = 'tablepress_' . $table_hash; // Attention: This string must not be longer than 45 characters!
			$output = get_transient( $transient_name );
			if ( false === $output || '' === $output ) {
				// Render/generate the table HTML, as it was not found in the cache.
				$_render->set_input( $table, $render_options );
				$output = $_render->get_output();
				// Save render output in a transient, set cache timeout to 24 hours.
				set_transient( $transient_name, $output, DAY_IN_SECONDS );
				// Update output caches list transient (necessary for cache invalidation upon table saving).
				$caches_list_transient_name = 'tablepress_c_' . md5( $table_id );
				$caches_list = get_transient( $caches_list_transient_name );
				if ( false === $caches_list ) {
					$caches_list = array();
				} else {
					$caches_list = (array) json_decode( $caches_list, true );
				}
				if ( ! in_array( $transient_name, $caches_list, true ) ) {
					$caches_list[] = $transient_name;
				}
				set_transient( $caches_list_transient_name, wp_json_encode( $caches_list, TABLEPRESS_JSON_OPTIONS ), 2 * DAY_IN_SECONDS );
			} else {
				/**
				 * Filters the cache hit comment message.
				 *
				 * @since 1.0.0
				 *
				 * @param string $comment The cache hit comment message.
				 */
				$output .= apply_filters( 'tablepress_cache_hit_comment', "<!-- #{$render_options['html_id']} from cache -->" );
			}
		} else {
			// Render/generate the table HTML, as no cache is to be used.
			$_render->set_input( $table, $render_options );
			$output = $_render->get_output();
		}

		// If DataTables is to be and can be used with this instance of a table, process its parameters and register the call for inclusion in the footer.
		if ( $render_options['use_datatables']
			&& $render_options['table_head']
			&& false !== strpos( $output, '<thead' ) // A `<thead>` tag is required.
			&& false === strpos( $output, ' colspan="' ) // `colspan` attributes are forbidden.
			&& false === strpos( $output, ' rowspan="' ) // `rowspan` attributes are forbidden.
			) {
			// Get options for the DataTables JavaScript library from the table's render options.
			$js_options = array();
			foreach ( array(
				'alternating_row_colors',
				'datatables_sort',
				'datatables_paginate',
				'datatables_paginate',
				'datatables_paginate_entries',
				'datatables_lengthchange',
				'datatables_filter',
				'datatables_info',
				'datatables_scrollx',
				'datatables_scrolly',
				'datatables_locale',
				'datatables_custom_commands',
			) as $option ) {
				$js_options[ $option ] = $render_options[ $option ];
			}
			/**
			 * Filters the JavaScript options for the table.
			 *
			 * The JavaScript options are determined from the settings on a table's "Edit" screen and the Shortcode parameters.
			 * They are part of the render options and can be overwritten with Shortcode parameters.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $js_options     The JavaScript options for the table.
			 * @param string $table_id       The current table ID.
			 * @param array  $render_options The render options for the table.
			 */
			$js_options = apply_filters( 'tablepress_table_js_options', $js_options, $table_id, $render_options );
			$this->shown_tables[ $table_id ]['instances'][ $render_options['html_id'] ] = $js_options;
			$this->_enqueue_datatables();
		}

		// Maybe print a list of used render options.
		if ( $render_options['shortcode_debug'] && is_user_logged_in() ) {
			$output .= '<pre>' . var_export( $render_options, true ) . '</pre>';
		}

		return $output;
	}

	/**
	 * Handle Shortcode [table-info id=<ID> field=<name> /].
	 *
	 * @since 1.0.0
	 *
	 * @param array $shortcode_atts List of attributes that where included in the Shortcode.
	 * @return string Text that replaces the Shortcode (error message or asked-for information).
	 */
	public function shortcode_table_info( $shortcode_atts ) {
		// Don't use `array` type hint in method declaration, as for empty Shortcodes like [table-info] or [table-info /], an empty string is passed, see WP Core #26927.
		$shortcode_atts = (array) $shortcode_atts;

		// Parse Shortcode attributes, only allow those that are specified.
		$default_shortcode_atts = array(
			'id'     => '',
			'field'  => '',
			'format' => '',
		);
		/**
		 * Filters the available/default attributes for the [table-info] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param array $default_shortcode_atts The [table-info] Shortcode default attributes.
		 */
		$default_shortcode_atts = apply_filters( 'tablepress_shortcode_table_info_default_shortcode_atts', $default_shortcode_atts );
		$shortcode_atts = shortcode_atts( $default_shortcode_atts, $shortcode_atts ); // Optional third argument left out on purpose. Use filter in the next line instead.
		/**
		 * Filters the attributes that were passed to the [table-info] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param array $shortcode_atts The attributes passed to the [table-info] Shortcode.
		 */
		$shortcode_atts = apply_filters( 'tablepress_shortcode_table_info_shortcode_atts', $shortcode_atts );

		/**
		 * Filters whether the output of the [table-info] Shortcode is overwritten/short-circuited.
		 *
		 * @since 1.0.0
		 *
		 * @param bool|string $overwrite      Whether the [table-info] output is overwritten. Return false for the regular content, and a string to overwrite the output.
		 * @param array       $shortcode_atts The attributes passed to the [table-info] Shortcode.
		 */
		$overwrite = apply_filters( 'tablepress_shortcode_table_info_overwrite', false, $shortcode_atts );
		if ( $overwrite ) {
			return $overwrite;
		}

		// Check, if a table with the given ID exists.
		$table_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', $shortcode_atts['id'] );
		if ( ! TablePress::$model_table->table_exists( $table_id ) ) {
			$message = "[table &#8220;{$table_id}&#8221; not found /]<br />\n";
			/** This filter is documented in controllers/controller-frontend.php */
			$message = apply_filters( 'tablepress_table_not_found_message', $message, $table_id );
			return $message;
		}

		// Load table, with table data, options, and visibility settings.
		$table = TablePress::$model_table->load( $table_id, true, true );
		if ( is_wp_error( $table ) ) {
			$message = "[table &#8220;{$table_id}&#8221; could not be loaded /]<br />\n";
			/** This filter is documented in controllers/controller-frontend.php */
			$message = apply_filters( 'tablepress_table_load_error_message', $message, $table_id, $table );
			return $message;
		}

		$field = preg_replace( '/[^a-z_]/', '', strtolower( $shortcode_atts['field'] ) );
		$format = preg_replace( '/[^a-z]/', '', strtolower( $shortcode_atts['format'] ) );

		// Generate output, depending on what information (field) was asked for.
		switch ( $field ) {
			case 'name':
			case 'description':
				$output = $table[ $field ];
				break;
			case 'last_modified':
				switch ( $format ) {
					case 'raw':
					case 'mysql':
						$output = $table['last_modified'];
						break;
					case 'human':
						$modified_timestamp = date_create( $table['last_modified'], wp_timezone() );
						$modified_timestamp = $modified_timestamp->getTimestamp();
						$current_timestamp = time();
						$time_diff = $current_timestamp - $modified_timestamp;
						// Time difference is only shown up to one week.
						if ( $time_diff >= 0 && $time_diff < WEEK_IN_SECONDS ) {
							$output = sprintf( __( '%s ago', 'default' ), human_time_diff( $modified_timestamp, $current_timestamp ) );
						} else {
							$output = TablePress::format_datetime( $table['last_modified'], '<br />' );
						}
						break;
					case 'date':
						$output = TablePress::format_datetime( $table['last_modified'], get_option( 'date_format' ) );
						break;
					case 'time':
						$output = TablePress::format_datetime( $table['last_modified'], get_option( 'time_format' ) );
						break;
					default:
						$output = TablePress::format_datetime( $table['last_modified'] );
						break;
				}
				break;
			case 'last_editor':
				$output = TablePress::get_user_display_name( $table['options']['last_editor'] );
				break;
			case 'author':
				$output = TablePress::get_user_display_name( $table['author'] );
				break;
			case 'number_rows':
				$output = count( $table['data'] );
				if ( 'raw' !== $format ) {
					if ( $table['options']['table_head'] ) {
						$output = $output - 1;
					}
					if ( $table['options']['table_foot'] ) {
						$output = $output - 1;
					}
				}
				break;
			case 'number_columns':
				$output = count( $table['data'][0] );
				break;
			default:
				$output = "[table-info field &#8220;{$field}&#8221; not found in table &#8220;{$table_id}&#8221; /]<br />\n";
				/**
				 * Filters the "table info field not found" message.
				 *
				 * @since 1.0.0
				 *
				 * @param string $output The "table info field not found" message.
				 * @param array  $table  The current table ID.
				 * @param string $field  The field that was not found.
				 * @param string $format The return format for the field.
				 */
				$output = apply_filters( 'tablepress_table_info_not_found_message', $output, $table, $field, $format );
		}

		/**
		 * Filters the output of the [table-info] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param string $output         The output of the [table-info] Shortcode.
		 * @param array  $table          The current table.
		 * @param array  $shortcode_atts The attributes passed to the [table-info] Shortcode.
		 */
		$output = apply_filters( 'tablepress_shortcode_table_info_output', $output, $table, $shortcode_atts );
		return $output;
	}

	/**
	 * Expand WP Search to also find posts and pages that have a search term in a table that is shown in them.
	 *
	 * This is done by looping through all search terms and TablePress tables and searching there for the search term,
	 * saving all tables's IDs that have a search term and then expanding the WP query to search for posts or pages that have the
	 * Shortcode for one of these tables in their content.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $search_sql Current part of the "WHERE" clause of the SQL statement used to get posts/pages from the WP database that is related to searching.
	 * @return string Eventually extended SQL "WHERE" clause, to also find posts/pages with Shortcodes in them.
	 */
	public function posts_search_filter( $search_sql ) {
		global $wpdb;

		if ( ! is_search() || ! is_main_query() ) {
			return $search_sql;
		}

		// Get variable that contains all search terms, parsed from $_GET['s'] by WP.
		$search_terms = get_query_var( 'search_terms' );
		if ( empty( $search_terms ) || ! is_array( $search_terms ) ) {
			return $search_sql;
		}

		// Load all table IDs and prime post meta cache for cached access to options and visibility settings of the tables, don't run filter hook.
		$table_ids = TablePress::$model_table->load_all( true, false );
		// Array of all search words that were found, and the table IDs where they were found.
		$query_result = array();

		foreach ( $table_ids as $table_id ) {
			// Load table, with table data, options, and visibility settings.
			$table = TablePress::$model_table->load( $table_id, true, true );

			if ( isset( $table['is_corrupted'] ) && $table['is_corrupted'] ) {
				// Do not search in corrupted tables.
				continue;
			}

			foreach ( $search_terms as $search_term ) {
				if ( ( $table['options']['print_name'] && false !== stripos( $table['name'], $search_term ) )
					|| ( $table['options']['print_description'] && false !== stripos( $table['description'], $search_term ) ) ) {
					// Found the search term in the name or description (and they are shown).
					$query_result[ $search_term ][] = $table_id; // Add table ID to result list.
					// No need to continue searching this search term in this table.
					continue;
				}

				// Search search term in visible table cells (without taking Shortcode parameters into account!).
				foreach ( $table['data'] as $row_idx => $table_row ) {
					if ( 0 === $table['visibility']['rows'][ $row_idx ] ) {
						// Row is hidden, so don't search in it.
						continue;
					}
					foreach ( $table_row as $col_idx => $table_cell ) {
						if ( 0 === $table['visibility']['columns'][ $col_idx ] ) {
							// Column is hidden, so don't search in it.
							continue;
						}
						// @TODO: Cells are not evaluated here, so math formulas are searched.
						if ( false !== stripos( $table_cell, $search_term ) ) {
							// Found the search term in the cell content.
							$query_result[ $search_term ][] = $table_id; // Add table ID to result list
							// No need to continue searching this search term in this table.
							continue 3;
						}
					}
				}
			}
		}

		// For all found table IDs for each search term, add additional OR statement to the SQL "WHERE" clause.

		// If $_GET['exact'] is set, WordPress doesn't use % in SQL LIKE clauses.
		$exact = get_query_var( 'exact' );
		$n = ( empty( $exact ) ) ? '%' : '';
		$search_sql = $wpdb->remove_placeholder_escape( $search_sql );
		foreach ( $query_result as $search_term => $table_ids ) {
			$search_term = esc_sql( $wpdb->esc_like( $search_term ) );
			$old_or = "OR ({$wpdb->posts}.post_content LIKE '{$n}{$search_term}{$n}')";
			$table_ids = implode( '|', $table_ids );
			$regexp = '\\\\[' . TablePress::$shortcode . ' id=(["\\\']?)(' . $table_ids . ')([\]"\\\' /])'; // ' needs to be single escaped, [ double escaped (with \\) in mySQL
			$new_or = $old_or . " OR ({$wpdb->posts}.post_content REGEXP '{$regexp}')";
			$search_sql = str_replace( $old_or, $new_or, $search_sql );
		}
		$search_sql = $wpdb->add_placeholder_escape( $search_sql );

		return $search_sql;
	}

	/**
	 * Callback function for rendering the tablepress/table block.
	 *
	 * @since 2.0.0
	 *
	 * @param array $block_attributes List of attributes that where included in the block settings.
	 * @return string Resulting HTML code for the table.
	 */
	public function table_block_render_callback( array $block_attributes ) {
		// Don't return anything if no table was selected.
		if ( '' === $block_attributes['id'] ) {
			return;
		}

		if ( '' !== trim( $block_attributes['parameters'] ) ) {
			$render_attributes = shortcode_parse_atts( $block_attributes['parameters'] );
		} else {
			$render_attributes = array();
		}
		$render_attributes['id'] = $block_attributes['id'];

		return $this->shortcode_table( $render_attributes );
	}

} // class TablePress_Frontend_Controller
