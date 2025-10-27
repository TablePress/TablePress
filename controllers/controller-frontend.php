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
	 * Whether to use the legacy CSS loading method of enqueuing all CSS files on all pages.
	 *
	 * @since 3.0.1
	 */
	public bool $use_legacy_css_loading = false;

	/**
	 * File name of the admin screens' parent page in the admin menu.
	 *
	 * @since 1.0.0
	 */
	public string $parent_page = 'middle';

	/**
	 * Whether TablePress admin screens are a top-level menu item in the admin menu.
	 *
	 * @since 1.0.0
	 */
	public bool $is_top_level_page = false;

	/**
	 * List of tables that are shown for the current request.
	 *
	 * @since 1.0.0
	 * @var array<string, array{count: int, instances: array<string, array<string, mixed>>}>
	 */
	protected array $shown_tables = array();

	/**
	 * List of registered DataTables datetime formats.
	 *
	 * @since 3.0.0
	 * @var string[]
	 */
	protected array $datatables_datetime_formats = array();

	/**
	 * Initiates Frontend functionality.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * Filters the admin menu parent page, which is needed for the construction of plugin URLs.
		 *
		 * @since 1.0.0
		 *
		 * @param string $parent_page Current admin menu parent page.
		 */
		$this->parent_page = apply_filters( 'tablepress_admin_menu_parent_page', TablePress::$model_options->get( 'admin_menu_parent_page' ) );
		$this->is_top_level_page = in_array( $this->parent_page, array( 'top', 'middle', 'bottom' ), true );

		/**
		 * Filters whether TablePress should load its frontend CSS files on all pages.
		 * For block themes, the default behavior is to only load the CSS files when a table is encountered on the page.
		 * If Elementor is active, the CSS is also loaded on the editor page.
		 *
		 * @since 3.0.1
		 *
		 * @param bool $use_legacy_css_loading Whether TablePress should load its frontend CSS files on all pages.
		 */
		$this->use_legacy_css_loading = apply_filters( 'tablepress_frontend_legacy_css_loading', ! wp_is_block_theme() || ( isset( $_GET['elementor-preview'] ) && is_plugin_active( 'elementor/elementor.php' ) ) );

		if ( $this->use_legacy_css_loading ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_css' ) );
		}

		add_action( 'wp_print_footer_scripts', array( $this, 'add_datatables_calls' ), 9 ); // Priority 9 so that this runs before `_wp_footer_scripts()`.

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
		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			// wp_register_block_metadata_collection() is only available since WP 6.7.
			wp_register_block_metadata_collection(
				TABLEPRESS_ABSPATH . 'blocks',
				TABLEPRESS_ABSPATH . 'blocks/blocks-manifest.php',
			);
		}
		register_block_type_from_metadata(
			TABLEPRESS_ABSPATH . 'blocks/table/block.json',
			array(
				'render_callback' => array( $this, 'table_block_render_callback' ),
			),
		);

		/**
		 * Register the TablePress Elementor widgets.
		 */
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_elementor_editor_styles' ), 10, 0 );
	}

	/**
	 * Registers TablePress Shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function init_shortcodes(): void {
		add_shortcode( TablePress::$shortcode, array( $this, 'shortcode_table' ) );
		add_shortcode( TablePress::$shortcode_info, array( $this, 'shortcode_table_info' ) );
	}

	/**
	 * Checks if the CSS files for TablePress default CSS and "Custom CSS" should be loaded.
	 *
	 * This function is only called when a [table /] Shortcode or "TablePress Table" block is evaluated, so that CSS files are only loaded when needed.
	 *
	 * @since 3.0.0
	 */
	public function maybe_enqueue_css(): void {
		// Bail early if the legacy CSS loading mechanism is used, as the files will then have been enqueued already.
		if ( $this->use_legacy_css_loading && ! doing_action( 'enqueue_block_assets' ) ) {
			return;
		}

		/*
		 * Bail early if the function is called from some action hook outside of the normal rendering process.
		 * These are often used by e.g. SEO plugins that render the content in additional contexts, e.g. to get an excerpt via an output buffer.
		 * In these cases, we don't want to enqueue the CSS, as it would likely not be printed on the page.
		 */
		if ( doing_action( 'wp_head' ) || doing_action( 'wp_footer' ) ) {
			return;
		}

		// Prevent repeated execution via a static variable.
		static $css_enqueued = false;
		if ( $css_enqueued && ! doing_action( 'enqueue_block_assets' ) ) {
			return;
		}
		$css_enqueued = true;

		$this->enqueue_css();
	}

	/**
	 * Enqueues CSS files for TablePress default CSS and "Custom CSS" (if desired).
	 *
	 * If styles have not been printed to the page (in the `<head>`), the TablePress CSS files will be enqueued.
	 * If styles have already been printed to the page, the TablePress CSS files will be printed right away (likely in the `<body`>).
	 *
	 * @since 1.0.0
	 */
	public function enqueue_css(): void {
		/**
		 * Filters whether the TablePress Default CSS code shall be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $use Whether the Default CSS shall be loaded. Default true.
		 */
		$use_default_css = apply_filters( 'tablepress_use_default_css', true );
		$use_custom_css = TablePress::$model_options->get( 'use_custom_css' );

		if ( ! $use_default_css && ! $use_custom_css ) {
			// Register a placeholder dependency, so that the handle is known for other styles.
			wp_register_style( 'tablepress-default', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			return;
		}

		$custom_css = TablePress::$model_options->get( 'custom_css' );
		$use_custom_css = $use_custom_css && '' !== $custom_css;
		$use_custom_css_file = $use_custom_css && TablePress::$model_options->get( 'use_custom_css_file' );
		/**
		 * Filters the "Custom CSS" version number that is appended to the enqueued CSS files
		 *
		 * @since 1.0.0
		 *
		 * @param int $version The "Custom CSS" version.
		 */
		$custom_css_version = (string) apply_filters( 'tablepress_custom_css_version', TablePress::$model_options->get( 'custom_css_version' ) );

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
			if ( did_action( 'wp_print_styles' ) ) {
				wp_print_styles( 'tablepress-default' );
			}
			return;
		}

		if ( $use_default_css ) {
			wp_enqueue_style( 'tablepress-default', $default_css_url, array(), TablePress::version );
		} else {
			// Register a placeholder dependency, so that the handle is known for other styles.
			wp_register_style( 'tablepress-default', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		}

		$use_custom_css_minified_file = ( $use_custom_css_file && ! SCRIPT_DEBUG && $tablepress_css->load_custom_css_from_file( 'minified' ) );
		if ( $use_custom_css_minified_file ) {
			$custom_css_minified_url = $tablepress_css->get_custom_css_location( 'minified', 'url' );
			wp_enqueue_style( 'tablepress-custom', $custom_css_minified_url, array( 'tablepress-default' ), $custom_css_version );
			if ( did_action( 'wp_print_styles' ) ) {
				wp_print_styles( 'tablepress-custom' );
			}
			return;
		}

		$use_custom_css_normal_file = ( $use_custom_css_file && $tablepress_css->load_custom_css_from_file( 'normal' ) );
		if ( $use_custom_css_normal_file ) {
			$custom_css_normal_url = $tablepress_css->get_custom_css_location( 'normal', 'url' );
			wp_enqueue_style( 'tablepress-custom', $custom_css_normal_url, array( 'tablepress-default' ), $custom_css_version );
			if ( did_action( 'wp_print_styles' ) ) {
				wp_print_styles( 'tablepress-custom' );
			}
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
				wp_add_inline_style( 'tablepress-default', $custom_css );
				if ( did_action( 'wp_print_styles' ) ) {
					wp_print_styles( 'tablepress-default' );
				}
				return;
			}
		}
	}

	/**
	 * Enqueues the DataTables JavaScript library and its dependencies.
	 *
	 * @since 3.0.0
	 */
	protected function enqueue_datatables_files(): void {
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

		$dependencies = array( 'jquery-core' );
		if ( ! empty( $this->datatables_datetime_formats ) ) {
			$dependencies[] = 'moment';
		}
		/**
		 * Filters the dependencies for the DataTables JavaScript library.
		 *
		 * @since 3.0.0
		 *
		 * @param string[] $dependencies The dependencies for the DataTables JS library.
		 */
		$dependencies = apply_filters( 'tablepress_datatables_js_dependencies', $dependencies );

		wp_enqueue_script( 'tablepress-datatables', $js_url, $dependencies, TablePress::version, true );
	}

	/**
	 * Adds the JavaScript code for the invocation of the DataTables JS library.
	 *
	 * @since 1.0.0
	 */
	public function add_datatables_calls(): void {
		// Prevent repeated execution (which would lead to DataTables error messages) via a static variable.
		static $datatables_calls_printed = false;
		if ( $datatables_calls_printed ) {
			return;
		}

		// Bail early if there are no TablePress tables on the page.
		if ( empty( $this->shown_tables ) ) {
			return;
		}

		/*
		 * Don't add the DataTables function calls in the scope of the block editor iframe.
		 * This is necessary for non-block themes, for others, the repeated execution check above is sufficient.
		 */
		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
			if ( ( $current_screen instanceof WP_Screen ) && $current_screen->is_block_editor() ) {
				return;
			}
		}

		// Filter out all tables that use DataTables.
		$shown_tables_with_datatables = array();
		foreach ( $this->shown_tables as $table_id => $table_store ) {
			if ( ! empty( $table_store['instances'] ) ) {
				$shown_tables_with_datatables[ (string) $table_id ] = $table_store;
			}
		}

		// Bail early if there are no tables with activated DataTables on the page.
		if ( empty( $shown_tables_with_datatables ) ) {
			return;
		}

		$this->enqueue_datatables_files();

		// Storage for the DataTables language strings.
		$datatables_language = array();
		// Generate the specific JS commands, depending on chosen features on the "Edit" screen and the Shortcode parameters.
		$commands = array();

		foreach ( $shown_tables_with_datatables as $table_id => $table_store ) {
			$table_id = (string) $table_id; // Ensure that the table ID is a string, as it comes from an array key where numeric strings are converted to integers.

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
						if ( str_ends_with( $language_file, '.php' ) ) {
							$datatables_strings = require $language_file;
							if ( ! is_array( $datatables_strings ) ) {
								$datatables_strings = array();
							}
						} elseif ( str_ends_with( $language_file, '.json' ) ) {
							$datatables_strings = file_get_contents( $language_file );
							$datatables_strings = json_decode( $datatables_strings, true ); // @phpstan-ignore argument.type
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
					 * @param array<string, mixed> $datatables_strings The language strings for DataTables.
					 * @param string               $datatables_locale  Current locale/language for the DataTables JS library.
					 */
					$datatables_language[ $datatables_locale ] = apply_filters( 'tablepress_datatables_language_strings', $datatables_strings, $datatables_locale );
				}
				$parameters['language'] = "language:DT_language['{$datatables_locale}']";

				// These parameters need to be added for performance gain or to overwrite unwanted default behavior.
				if ( $js_options['datatables_sort'] ) {
					// No initial sort.
					$parameters['order'] = 'order:[]';
					// Don't add additional classes, to speed up sorting.
					$parameters['orderClasses'] = 'orderClasses:false';
				}

				// The following options are activated by default, so we only need to "false" them if we don't want them, but don't need to "true" them if we do.
				if ( ! $js_options['datatables_sort'] ) {
					$parameters['ordering'] = 'ordering:false';
				}
				if ( $js_options['datatables_paginate'] ) {
					$parameters['pagingType'] = "pagingType:'simple_numbers'";
					if ( $js_options['datatables_lengthchange'] ) {
						$length_menu = array( 10, 25, 50, 100 );
						if ( ! in_array( $js_options['datatables_paginate_entries'], $length_menu, true ) ) {
							$length_menu[] = $js_options['datatables_paginate_entries'];
							sort( $length_menu, SORT_NUMERIC );
							$parameters['lengthMenu'] = 'lengthMenu:[' . implode( ',', $length_menu ) . ']';
						}
					} else {
						$parameters['lengthChange'] = 'lengthChange:false';
					}
					if ( 10 !== $js_options['datatables_paginate_entries'] ) {
						$parameters['pageLength'] = "pageLength:{$js_options['datatables_paginate_entries']}";
					}
				} else {
					$parameters['paging'] = 'paging:false';
				}
				if ( ! $js_options['datatables_filter'] ) {
					$parameters['searching'] = 'searching:false';
				}
				if ( ! $js_options['datatables_info'] ) {
					$parameters['info'] = 'info:false';
				}
				if ( $js_options['datatables_scrollx'] ) {
					$parameters['scrollX'] = 'scrollX:true';
				}
				if ( false !== $js_options['datatables_scrolly'] ) {
					$parameters['scrollY'] = 'scrollY:"' . preg_replace( '#[^0-9a-z.%]#', '', $js_options['datatables_scrolly'] ) . '"';
					$parameters['scrollCollapse'] = 'scrollCollapse:true';
				}
				if ( '' !== $js_options['datatables_custom_commands'] ) {
					$parameters['custom_commands'] = trim( $js_options['datatables_custom_commands'] ); // Remove leading and trailing whitespace.
					$parameters['custom_commands'] = trim( $parameters['custom_commands'], ',' ); // Remove potentially leading and trailing commas to prevent JS script errors.
				}

				/**
				 * Filters the parameters that are passed to the DataTables JavaScript library.
				 *
				 * @since 1.0.0
				 *
				 * @param array<string, mixed> $parameters The parameters for the DataTables JS library.
				 * @param string               $table_id   The current table ID.
				 * @param string               $html_id    The ID of the table HTML element.
				 * @param array<string, mixed> $js_options The options for the JS library.
				 */
				$parameters = apply_filters( 'tablepress_datatables_parameters', $parameters, $table_id, $html_id, $js_options );

				// If an existing parameter is set as an object key in the "Custom Commands", remove its separate value, to allow for full overrides.
				if ( isset( $parameters['custom_commands'] ) && '' !== $parameters['custom_commands'] ) {
					$parameters_in_custom_commands = TablePress::extract_keys_from_js_object_string( '{' . $parameters['custom_commands'] . '}' );
					foreach ( $parameters_in_custom_commands as $parameter_in_custom_commands ) {
						unset( $parameters[ $parameter_in_custom_commands ] );
					}
				}

				$name = substr( $html_id, 11 ); // Remove "tablepress-" from the HTML ID.
				$name = "DT_TP['" . str_replace( '-', '_', $name ) . "']";
				$parameters = implode( ',', $parameters );
				$parameters = ( ! empty( $parameters ) ) ? '{' . $parameters . '}' : '';

				$command = "{$name} = new DataTable('#{$html_id}',{$parameters});";
				/**
				 * Filters the JavaScript command that invokes the DataTables JavaScript library on one table.
				 *
				 * @since 1.0.0
				 *
				 * @param string               $command    The JS command for the DataTables JS library.
				 * @param string               $html_id    The ID of the table HTML element.
				 * @param string               $parameters The parameters for the DataTables JS library.
				 * @param string               $table_id   The current table ID.
				 * @param array<string, mixed> $js_options The options for the JS library.
				 * @param string               $name       The name of the DataTable instance.
				 */
				$command = apply_filters( 'tablepress_datatables_command', $command, $html_id, $parameters, $table_id, $js_options, $name );
				if ( ! empty( $command ) ) {
					$commands[] = $command;
				}
			} // foreach table instance
		} // foreach table ID

		// DataTables language/translation handling.
		if ( ! empty( $datatables_language ) ) {
			$datatables_language_command = wp_json_encode( $datatables_language, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT );
			$datatables_language_command = "var DT_language={$datatables_language_command};\n";
		} else {
			$datatables_language_command = '';
		}

		// DataTables datetime format string handling.
		if ( ! empty( $this->datatables_datetime_formats ) ) {
			// Create a command like `DataTable.datetime("MM/DD/YYYY");DataTable.datetime("DD.MM.YYYY");`.
			$datatables_datetime_command = implode(
				'',
				array_map(
					static function ( string $datetime_format ): string {
						$datetime_format = wp_json_encode( $datetime_format, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
						return "DataTable.datetime({$datetime_format});";
					},
					$this->datatables_datetime_formats,
				)
			) . "\n";
		} else {
			$datatables_datetime_command = '';
		}

		/**
		 * Filters the JavaScript code for the DataTables JavaScript library that initializes the automatically detected date/time formats via moment.js.
		 *
		 * @since 3.0.0
		 *
		 * @param string   $datatables_datetime_command The JS code for the DataTables JS library that initializes the date/time formats.
		 * @param string[] $datatables_datetime_formats The date/time formats for moment.js.
		 */
		$datatables_datetime_command = apply_filters( 'tablepress_datatables_datetime_command', $datatables_datetime_command, $this->datatables_datetime_formats );

		$datatables_pre_commands = $datatables_language_command . $datatables_datetime_command;

		$commands = implode( "\n", $commands );
		/**
		 * Filters the JavaScript commands that invoke the DataTables JavaScript library on all tables on the page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $commands The JS commands for the DataTables JS library.
		 */
		$commands = apply_filters( 'tablepress_all_datatables_commands', $commands );
		if ( '' === $commands ) {
			return;
		}

		$script_template = <<<'JS'
			var DT_TP = {};
			jQuery(($)=>{
			%1$s%2$s
			});
			JS;
		/**
		 * Filters the script/jQuery wrapper code for the DataTables commands calls.
		 *
		 * @since 1.14.0
		 *
		 * @param string $script_template Default script/jQuery wrapper code for the DataTables commands calls.
		 */
		$script_template = apply_filters( 'tablepress_all_datatables_commands_wrapper', $script_template );

		$script = sprintf( $script_template, $datatables_pre_commands, $commands );
		wp_add_inline_script( 'tablepress-datatables', $script );

		// Prevent repeated execution (which would lead to DataTables error messages) via a static variable.
		$datatables_calls_printed = true;
	}

	/**
	 * Handles the  Shortcode [table id=<ID> /].
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|string $shortcode_atts List of attributes that where included in the Shortcode. An empty string for empty Shortcodes like [table] or [table /].
	 * @return string Resulting HTML code for the table with the ID <ID>.
	 */
	public function shortcode_table( /* array|string */ $shortcode_atts ): string {
		$shortcode_atts = (array) $shortcode_atts;

		$this->maybe_enqueue_css();

		$_render = TablePress::load_class( 'TablePress_Render', 'class-render.php', 'classes' );

		$default_shortcode_atts = $_render->get_default_render_options();
		/**
		 * Filters the available/default attributes for the [table] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $default_shortcode_atts The [table] Shortcode default attributes.
		 */
		$default_shortcode_atts = apply_filters( 'tablepress_shortcode_table_default_shortcode_atts', $default_shortcode_atts );
		// Parse Shortcode attributes, only allow those that are specified.
		$shortcode_atts = shortcode_atts( $default_shortcode_atts, $shortcode_atts ); // Optional third argument left out on purpose. Use filter in the next line instead.
		/**
		 * Filters the attributes that were passed to the [table] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $shortcode_atts The attributes passed to the [table] Shortcode.
		 */
		$shortcode_atts = apply_filters( 'tablepress_shortcode_table_shortcode_atts', $shortcode_atts );

		// Check, if a table with the given ID exists.
		$table_id = (string) preg_replace( '/[^a-zA-Z0-9_-]/', '', $shortcode_atts['id'] );
		if ( ! TablePress::$model_table->table_exists( $table_id ) ) {
			$message = "&#91;table “{$table_id}” not found /&#93;<br />\n";
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
			$message = "&#91;table “{$table_id}” could not be loaded /&#93;<br />\n";
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
			$message = "<div>Attention: The internal data of table “{$table_id}” is corrupted!</div>";
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

		if ( ! is_null( $shortcode_atts['datatables_custom_commands'] ) ) {
			/**
			 * Filters whether the "datatables_custom_commands" Shortcode parameter is disabled.
			 *
			 * By default, the "datatables_custom_commands" Shortcode parameter is disabled for security reasons.
			 *
			 * @since 1.0.0
			 *
			 * @param bool $disable Whether to disable the "datatables_custom_commands" Shortcode parameter. Default true.
			 */
			if ( apply_filters( 'tablepress_disable_custom_commands_shortcode_parameter', true ) ) {
				$shortcode_atts['datatables_custom_commands'] = null;
			} else {
				// Convert the HTML entity `&amp;` back to `&` manually, as entities in Shortcodes in normal text paragraphs are sometimes double-encoded.
				$shortcode_atts['datatables_custom_commands'] = str_replace( '&amp;', '&', $shortcode_atts['datatables_custom_commands'] );
				// Convert HTML entities like `&lt;`, `&lsqb;`, `&#91;`, and `&amp;` back to their respective characters.
				$shortcode_atts['datatables_custom_commands'] = html_entity_decode( $shortcode_atts['datatables_custom_commands'], ENT_QUOTES | ENT_HTML5, get_option( 'blog_charset' ) );
			}
		}

		// Determine options to use (if set in Shortcode, use those, otherwise use stored options, from the "Edit" screen).
		$render_options = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( is_null( $value ) && isset( $table['options'][ $key ] ) ) {
				// Use the table's stored option value, if the Shortcode parameter was not set.
				$render_options[ $key ] = $table['options'][ $key ];
			} elseif ( is_string( $value ) ) {
				// Convert strings 'true' or 'false' to boolean, keep others.
				$value_lowercase = strtolower( $value );
				if ( 'true' === $value_lowercase ) {
					$render_options[ $key ] = true;
				} elseif ( 'false' === $value_lowercase ) {
					$render_options[ $key ] = false;
				} else {
					$render_options[ $key ] = $value;
				}
			} else {
				// Keep all other values.
				$render_options[ $key ] = $value;
			}
		}

		// Backward compatibility: Convert boolean or numeric string "table_head" and "table_foot" options to integer.
		$render_options['table_head'] = absint( $render_options['table_head'] );
		$render_options['table_foot'] = absint( $render_options['table_foot'] );

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
		 * @param int    $count    Number of copies of the table with this table ID on the page.
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
		if ( is_user_logged_in() && ! $render_options['block_preview'] && apply_filters( 'tablepress_edit_link_below_table', true, $table['id'] ) && current_user_can( 'tablepress_edit_table', $table['id'] ) ) {
			$render_options['edit_table_url'] = TablePress::url( array( 'action' => 'edit', 'table_id' => $table['id'] ) );
		}

		/**
		 * Filters the render options for the table.
		 *
		 * The render options are determined from the settings on a table's "Edit" screen and the Shortcode parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $render_options The render options for the table.
		 * @param array<string, mixed> $table          The current table.
		 */
		$render_options = apply_filters( 'tablepress_table_render_options', $render_options, $table );

		// Backward compatibility: Convert boolean "table_head" and "table_foot" options to integer, in case they were overwritten via the filter hook.
		$render_options['table_head'] = absint( $render_options['table_head'] );
		$render_options['table_foot'] = absint( $render_options['table_foot'] );

		// Check if table output shall and can be loaded from the transient cache, otherwise generate the output.
		if ( $render_options['cache_table_output'] && ! is_user_logged_in() ) {
			// Hash the Render Options array to get a unique cache identifier.
			$table_hash = md5( wp_json_encode( $render_options, TABLEPRESS_JSON_OPTIONS ) ); // @phpstan-ignore argument.type
			$transient_name = 'tablepress_' . $table_hash; // Attention: This string must not be longer than 45 characters!
			$output = get_transient( $transient_name );
			if ( false === $output || '' === $output ) {
				// Render/generate the table HTML, as it was not found in the cache.
				$_render->set_input( $table, $render_options );
				$output = $_render->get_output( 'html' );
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
			$output = $_render->get_output( 'html' );
		}

		// If DataTables is to be and can be used with this instance of a table, process its parameters and register the call for inclusion in the footer.
		if ( $render_options['use_datatables']
			&& 0 < $render_options['table_head']
			&& ! str_contains( $output, 'tbody-has-connected-cells' ) // The Render class adds this CSS class to the `<table>` element if the table has connected cells in the `<tbody>`.
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
			 * @param array<string, mixed>  $js_options     The JavaScript options for the table.
			 * @param string                $table_id       The current table ID.
			 * @param array<string, mixed>  $render_options The render options for the table.
			 */
			$js_options = apply_filters( 'tablepress_table_js_options', $js_options, $table_id, $render_options );

			$this->shown_tables[ $table_id ]['instances'][ (string) $render_options['html_id'] ] = $js_options;

			// DataTables datetime format string handling.
			if ( '' !== $render_options['datatables_datetime'] ) {
				$render_options['datatables_datetime'] = explode( '|', $render_options['datatables_datetime'] );
				foreach ( $render_options['datatables_datetime'] as $datetime_format ) {
					$datetime_format = trim( $datetime_format );
					if ( '' !== $datetime_format && ! in_array( $datetime_format, $this->datatables_datetime_formats, true ) ) {
						$this->datatables_datetime_formats[] = $datetime_format;
					}
				}
			}
		}

		// Maybe print a list of used render options.
		if ( $render_options['shortcode_debug'] && is_user_logged_in() ) {
			$output .= '<pre>' . esc_html( wp_json_encode( $render_options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</pre>'; // @phpstan-ignore argument.type
		}

		return $output;
	}

	/**
	 * Handles the Shortcode [table-info id=<ID> field=<name> /].
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>|string $shortcode_atts List of attributes that where included in the Shortcode. An empty string for empty Shortcodes like [table] or [table /].
	 * @return string Text that replaces the Shortcode (error message or asked-for information).
	 */
	public function shortcode_table_info( /* array|string */ $shortcode_atts ): string {
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
		 * @param array<string, mixed> $default_shortcode_atts The [table-info] Shortcode default attributes.
		 */
		$default_shortcode_atts = apply_filters( 'tablepress_shortcode_table_info_default_shortcode_atts', $default_shortcode_atts );
		$shortcode_atts = shortcode_atts( $default_shortcode_atts, $shortcode_atts ); // Optional third argument left out on purpose. Use filter in the next line instead.
		/**
		 * Filters the attributes that were passed to the [table-info] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $shortcode_atts The attributes passed to the [table-info] Shortcode.
		 */
		$shortcode_atts = apply_filters( 'tablepress_shortcode_table_info_shortcode_atts', $shortcode_atts );

		/**
		 * Filters whether the output of the [table-info] Shortcode is overwritten/short-circuited.
		 *
		 * @since 1.0.0
		 *
		 * @param false|string         $overwrite      Whether the [table-info] output is overwritten. Return false for the regular content, and a string to overwrite the output.
		 * @param array<string, mixed> $shortcode_atts The attributes passed to the [table-info] Shortcode.
		 */
		$overwrite = apply_filters( 'tablepress_shortcode_table_info_overwrite', false, $shortcode_atts );
		if ( is_string( $overwrite ) ) {
			return $overwrite;
		}

		// Check, if a table with the given ID exists.
		$table_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', $shortcode_atts['id'] );
		if ( ! TablePress::$model_table->table_exists( $table_id ) ) {
			$message = "&#91;table “{$table_id}” not found /&#93;<br />\n";
			/** This filter is documented in controllers/controller-frontend.php */
			$message = apply_filters( 'tablepress_table_not_found_message', $message, $table_id );
			return $message;
		}

		// Load table, with table data, options, and visibility settings.
		$table = TablePress::$model_table->load( $table_id, true, true );
		if ( is_wp_error( $table ) ) {
			$message = "&#91;table “{$table_id}” could not be loaded /&#93;<br />\n";
			/** This filter is documented in controllers/controller-frontend.php */
			$message = apply_filters( 'tablepress_table_load_error_message', $message, $table_id, $table );
			return $message;
		}

		$field = (string) preg_replace( '/[^a-z_]/', '', strtolower( $shortcode_atts['field'] ) );
		$format = (string) preg_replace( '/[^a-z]/', '', strtolower( $shortcode_atts['format'] ) );

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
						if ( false === $modified_timestamp ) {
							$modified_timestamp = $table['last_modified'];
						} else {
							$modified_timestamp = $modified_timestamp->getTimestamp();
						}
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
					$output -= $table['options']['table_head'];
					$output -= $table['options']['table_foot'];
				}
				break;
			case 'number_columns':
				$output = count( $table['data'][0] );
				break;
			default:
				$output = "&#91;table-info field “{$field}” not found in table “{$table_id}” /&#93;<br />\n";
				/**
				 * Filters the "table info field not found" message.
				 *
				 * @since 1.0.0
				 *
				 * @param string               $output The "table info field not found" message.
				 * @param array<string, mixed> $table  The current table.
				 * @param string               $field  The field that was not found.
				 * @param string               $format The return format for the field.
				 */
				$output = apply_filters( 'tablepress_table_info_not_found_message', $output, $table, $field, $format );
		}

		/**
		 * Filters the output of the [table-info] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param string                $output         The output of the [table-info] Shortcode.
		 * @param array<string, mixed>  $table          The current table.
		 * @param array<string, mixed>  $shortcode_atts The attributes passed to the [table-info] Shortcode.
		 */
		$output = apply_filters( 'tablepress_shortcode_table_info_output', $output, $table, $shortcode_atts );
		return $output;
	}

	/**
	 * Expands the WP Search to also find posts and pages that have a search term in a table that is shown in them.
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
	public function posts_search_filter( /* string */ $search_sql ): string {
		// Don't use a type hint in the method declaration as there can be cases where `null` is passed to the filter hook callback somehow.

		global $wpdb;

		// Protect against cases where `null` is somehow passed to the filter hook callback.
		if ( ! is_string( $search_sql ) ) { // @phpstan-ignore function.alreadyNarrowedType (The `is_string()` check is needed as the input is coming from a filter hook.)
			return '';
		}

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

		$fn_stripos = function_exists( 'mb_stripos' ) ? 'mb_stripos' : 'stripos';

		foreach ( $table_ids as $table_id ) {
			// Load table, with table data, options, and visibility settings.
			$table = TablePress::$model_table->load( $table_id, true, true );

			// Skip tables that could not be loaded.
			if ( is_wp_error( $table ) ) {
				continue;
			}

			// Do not search in corrupted tables.
			if ( isset( $table['is_corrupted'] ) && $table['is_corrupted'] ) {
				continue;
			}

			foreach ( $search_terms as $search_term ) {
				if ( ( $table['options']['print_name'] && false !== $fn_stripos( $table['name'], (string) $search_term ) )
					|| ( $table['options']['print_description'] && false !== $fn_stripos( $table['description'], (string) $search_term ) ) ) {
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
						// @todo Cells are not evaluated here, so math formulas are searched.
						if ( false !== $fn_stripos( $table_cell, (string) $search_term ) ) {
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
			$old_or = "OR ({$wpdb->posts}.post_content LIKE '{$n}{$search_term}{$n}')"; // @phpstan-ignore encapsedStringPart.nonString (The esc_sql() call above returns a string, as a string is passed.)
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
	 * @param array<string, string> $block_attributes List of attributes that where included in the block settings.
	 * @return string Resulting HTML code for the table.
	 */
	public function table_block_render_callback( array $block_attributes ): string {
		// Don't return anything if no table was selected.
		if ( '' === $block_attributes['id'] ) {
			return '';
		}

		if ( '' !== trim( $block_attributes['parameters'] ) ) {
			$render_attributes = shortcode_parse_atts( $block_attributes['parameters'] );
		} else {
			$render_attributes = array();
		}
		$render_attributes['id'] = $block_attributes['id'];

		return $this->shortcode_table( $render_attributes );
	}

	/**
	 * Registers the TablePress Elementor widgets.
	 *
	 * @since 3.1.0
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_elementor_widgets( \Elementor\Widgets_Manager $widgets_manager ): void {
		TablePress::load_file( 'class-elementor-widget-table.php', 'classes' );
		$widgets_manager->register( new TablePress\Elementor\TablePressTableWidget() ); // @phpstan-ignore method.notFound (Elementor methods are not in the stubs.)
	}

	/**
	 * Enqueues the TablePress Elementor Editor CSS styles.
	 *
	 * @since 3.1.0
	 */
	public function enqueue_elementor_editor_styles(): void {
		$svg_url = plugins_url( 'admin/img/tablepress-editor-button.svg', TABLEPRESS__FILE__ );
		wp_register_style( 'tablepress-elementor', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_add_inline_style(
			'tablepress-elementor',
			<<<CSS
			.elementor-panel .elementor-element .icon:has(> .tablepress-elementor-icon) {
				height: 43.5px;
			}
			.tablepress-elementor-icon {
				display: inline-block;
				height: 28px;
				width: 28px;
				background-image: url({$svg_url});
				background-repeat: no-repeat;
				background-position: center;
				background-size: 28px auto;
			}
			CSS
		);
		wp_enqueue_style( 'tablepress-elementor' );
	}

} // class TablePress_Frontend_Controller
