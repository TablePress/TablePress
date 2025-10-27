<?php
/**
 * TablePress Class
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress class
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @since 1.0.0
 */
abstract class TablePress {

	/**
	 * TablePress version.
	 *
	 * Increases whenever a new plugin version is released.
	 *
	 * @since 1.0.0
	 * @const string
	 */
	public const version = '3.2.5'; // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

	/**
	 * TablePress internal plugin version ("options scheme" version).
	 *
	 * Increases whenever the scheme for the plugin options changes, or on a plugin update.
	 *
	 * @since 1.0.0
	 * @const int
	 */
	public const db_version = 119; // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

	/**
	 * TablePress "table scheme" (data format structure) version.
	 *
	 * Increases whenever the scheme for a $table changes,
	 * used to be able to update plugin options and table scheme independently.
	 *
	 * @since 1.0.0
	 * @const int
	 */
	public const table_scheme_version = 3; // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

	/**
	 * Instance of the Options Model.
	 *
	 * @since 1.3.0
	 */
	public static \TablePress_Options_Model $model_options;

	/**
	 * Instance of the Table Model.
	 *
	 * @since 1.3.0
	 */
	public static \TablePress_Table_Model $model_table;

	/**
	 * Instance of the controller.
	 *
	 * @since 1.0.0
	 */
	public static \TablePress_Frontend_Controller $controller;

	/**
	 * Name of the Shortcode to show a TablePress table.
	 *
	 * Should only be modified through the filter hook 'tablepress_table_shortcode'.
	 *
	 * @since 1.0.0
	 */
	public static string $shortcode = 'table';

	/**
	 * Name of the Shortcode to show extra information of a TablePress table.
	 *
	 * Should only be modified through the filter hook 'tablepress_table_info_shortcode'.
	 *
	 * @since 1.0.0
	 */
	public static string $shortcode_info = 'table-info';

	/**
	 * List of TablePress premium modules.
	 *
	 * @since 2.1.0
	 * @var array<string, array{name: string, description: string, category: string, class: string, incompatible_classes: string[], minimum_plan: string, default_active: bool}>
	 */
	public static array $modules = array();

	/**
	 * Start-up TablePress (run on WordPress "init") and load the controller for the current state.
	 *
	 * @since 1.0.0
	 */
	public static function run(): void {
		/**
		 * Fires before TablePress is loaded.
		 *
		 * The `tablepress_loaded` action hook might be a better choice in most situations, as TablePress options will then be available.
		 *
		 * @since 1.0.0
		 */
		do_action( 'tablepress_run' );

		/**
		 * Filters the string that is used as the [table] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param string $shortcode The [table] Shortcode string.
		 */
		self::$shortcode = apply_filters( 'tablepress_table_shortcode', self::$shortcode );
		/**
		 * Filters the string that is used as the [table-info] Shortcode.
		 *
		 * @since 1.0.0
		 *
		 * @param string $shortcode_info The [table-info] Shortcode string.
		 */
		self::$shortcode_info = apply_filters( 'tablepress_table_info_shortcode', self::$shortcode_info );

		// Load modals for table and options, to be accessible from everywhere via `TablePress::$model_options` and `TablePress::$model_table`.
		self::$model_options = self::load_model( 'options' );
		self::$model_table = self::load_model( 'table' );

		// Exit early, i.e. before a controller is loaded, if TablePress functionality is likely not needed.
		$exit_early = false;
		if ( ( isset( $_SERVER['SCRIPT_FILENAME'] ) && 'wp-login.php' === basename( $_SERVER['SCRIPT_FILENAME'] ) ) // Detect the WordPress Login screen.
			|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
			|| wp_doing_cron() ) {
			$exit_early = true;
		}
		/**
		 * Filters whether TablePress should exit early, e.g. during wp-login.php, XML-RPC, and WP-Cron requests.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $exit_early Whether TablePress should exit early.
		 */
		if ( apply_filters( 'tablepress_exit_early', $exit_early ) ) {
			return;
		}

		if ( is_admin() ) {
			$controller = 'admin';
			if ( wp_doing_ajax() ) {
				$controller = 'admin_ajax';
			}
			self::load_controller( $controller );
		}
		// Load the frontend controller in all scenarios, so that Shortcode render functions are always available.
		self::$controller = self::load_controller( 'frontend' );

		// Add filters and actions for the integration into the WP WXR exporter and importer.
		add_action( 'wp_import_insert_post', array( TablePress::$model_table, 'add_table_id_on_wp_import' ), 10, 4 ); // phpcs:ignore Squiz.Classes.SelfMemberReference.NotUsed
		add_filter( 'wp_import_post_meta', array( TablePress::$model_table, 'prevent_table_id_post_meta_import_on_wp_import' ), 10, 3 ); // phpcs:ignore Squiz.Classes.SelfMemberReference.NotUsed
		add_filter( 'wxr_export_skip_postmeta', array( TablePress::$model_table, 'add_table_id_to_wp_export' ), 10, 3 ); // phpcs:ignore Squiz.Classes.SelfMemberReference.NotUsed

		/**
		 * Fires after TablePress is loaded.
		 *
		 * The `tablepress_run` action hook can be used if code has to run before TablePress is loaded.
		 *
		 * @since 2.0.0
		 */
		do_action( 'tablepress_loaded' );
	}

	/**
	 * Load a file with require_once(), after running it through a filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file   Name of the PHP file.
	 * @param string $folder Name of the folder with the file.
	 */
	public static function load_file( string $file, string $folder ): void {
		$full_path = TABLEPRESS_ABSPATH . $folder . '/' . $file;
		/**
		 * Filters the full path of a file that shall be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $full_path Full path of the file that shall be loaded.
		 * @param string $file      File name of the file that shall be loaded.
		 * @param string $folder    Folder name of the file that shall be loaded.
		 */
		$full_path = apply_filters( 'tablepress_load_file_full_path', $full_path, $file, $folder );
		if ( $full_path ) {
			require_once $full_path;
		}
	}

	/**
	 * Create a new instance of the $class_name, which is stored in $file in the $folder subfolder
	 * of the plugin's directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $class_name Name of the class.
	 * @param string              $file       Name of the PHP file with the class.
	 * @param string              $folder     Name of the folder with $class_name's $file.
	 * @param mixed[]|string|null $params     Optional. Parameters that are passed to the constructor of $class_name.
	 * @return object Initialized instance of the class.
	 */
	public static function load_class( string $class_name, string $file, string $folder, /* ?array|string */ $params = null ): object {
		/**
		 * Filters name of the class that shall be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $class_name Name of the class that shall be loaded.
		 */
		$class_name = apply_filters( 'tablepress_load_class_name', $class_name );
		if ( ! class_exists( $class_name, false ) ) {
			self::load_file( $file, $folder );
		}
		$the_class = new $class_name( $params );
		return $the_class;
	}

	/**
	 * Create a new instance of the $model, which is stored in the "models" subfolder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $model Name of the model.
	 * @return object Instance of the initialized model.
	 */
	public static function load_model( string $model ): object {
		// Model Base Class.
		self::load_file( 'class-model.php', 'classes' );
		// Make first letter uppercase for a better looking naming pattern.
		$ucmodel = ucfirst( $model );
		$the_model = self::load_class( "TablePress_{$ucmodel}_Model", "model-{$model}.php", 'models' );
		return $the_model;
	}

	/**
	 * Create a new instance of the $view, which is stored in the "views" subfolder, and set it up with $data.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $view Name of the view to load.
	 * @param array<string, mixed> $data Optional. Parameters/PHP variables that shall be available to the view.
	 * @return object Instance of the initialized view, already set up, just needs to be rendered.
	 */
	public static function load_view( string $view, array $data = array() ): object {
		// View Base Class.
		self::load_file( 'class-view.php', 'classes' );
		// Make first letter uppercase for a better looking naming pattern.
		$ucview = ucfirst( $view );
		$the_view = self::load_class( "TablePress_{$ucview}_View", "view-{$view}.php", 'views' );
		$the_view->setup( $view, $data );
		return $the_view;
	}

	/**
	 * Create a new instance of the $controller, which is stored in the "controllers" subfolder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $controller Name of the controller.
	 * @return object Instance of the initialized controller.
	 */
	public static function load_controller( string $controller ): object {
		// Controller Base Class.
		self::load_file( 'class-controller.php', 'classes' );
		// Make first letter uppercase for a better looking naming pattern.
		$uccontroller = ucfirst( $controller );
		$the_controller = self::load_class( "TablePress_{$uccontroller}_Controller", "controller-{$controller}.php", 'controllers' );
		return $the_controller;
	}

	/**
	 * Generate the complete nonce string, from the nonce base, the action and an item, e.g. tablepress_delete_table_3.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $action Action for which the nonce is needed.
	 * @param string|false $item   Optional. Item for which the action will be performed, like "table". false if no item should be used in the nonce.
	 * @return string The resulting nonce string.
	 */
	public static function nonce( string $action, /* string|false */ $item = false ): string {
		$nonce = "tablepress_{$action}";
		if ( $item ) {
			$nonce .= "_{$item}";
		}
		return $nonce;
	}

	/**
	 * Check whether a nonce string is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $action    Action for which the nonce should be checked.
	 * @param string|false $item      Optional. Item for which the action should be performed, like "table". false if no item should be used in the nonce.
	 * @param string       $query_arg Optional. Name of the nonce query string argument in $_POST.
	 * @param bool         $ajax Whether the nonce comes from an AJAX request.
	 */
	public static function check_nonce( string $action, /* string|false */ $item = false, string $query_arg = '_wpnonce', bool $ajax = false ): void {
		$nonce_action = self::nonce( $action, $item );
		if ( $ajax ) {
			check_ajax_referer( $nonce_action, $query_arg );
		} else {
			check_admin_referer( $nonce_action, $query_arg );
		}
	}

	/**
	 * Calculate the column index (number) of a column header string (example: A is 1, AA is 27, ...).
	 *
	 * For the opposite, @see number_to_letter().
	 *
	 * @since 1.0.0
	 *
	 * @param string $column Column string.
	 * @return int Column number, 1-based.
	 */
	public static function letter_to_number( string $column ): int {
		$column = (string) preg_replace( '/[^A-Za-z]/', '', $column );
		$column = strtoupper( $column );
		$count = strlen( $column );
		$number = 0;
		for ( $i = 0; $i < $count; $i++ ) {
			$number += ( ord( $column[ $count - 1 - $i ] ) - 64 ) * 26 ** $i;
		}
		return $number;
	}

	/**
	 * "Calculate" the column header string of a column index (example: 2 is B, AB is 28, ...).
	 *
	 * For the opposite, @see letter_to_number().
	 *
	 * @since 1.0.0
	 *
	 * @param int $number Column number, 1-based.
	 * @return string Column string.
	 */
	public static function number_to_letter( int $number ): string {
		$column = '';
		while ( $number > 0 ) {
			$column = chr( 65 + ( ( $number - 1 ) % 26 ) ) . $column;
			$number = intdiv( $number - 1, 26 );
		}
		return $column;
	}

	/**
	 * Get a nice looking date and time string from the mySQL format of datetime strings for output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $datetime_string     DateTime string, often in mySQL format..
	 * @param string $separator_or_format Optional. Separator between date and time, or format string.
	 * @return string Nice looking string with the date and time.
	 */
	public static function format_datetime( string $datetime_string, string $separator_or_format = ' ' ): string {
		$timezone = wp_timezone();
		$datetime = date_create( $datetime_string, $timezone );
		if ( false === $datetime ) {
			return $datetime_string;
		}
		$timestamp = $datetime->getTimestamp();

		switch ( $separator_or_format ) {
			case ' ':
			case '<br />':
			case '<br/>':
			case '<br>':
				$date = wp_date( get_option( 'date_format' ), $timestamp, $timezone );
				$time = wp_date( get_option( 'time_format' ), $timestamp, $timezone );
				$output = "{$date}{$separator_or_format}{$time}";
				break;
			default:
				$output = (string) wp_date( $separator_or_format, $timestamp, $timezone );
				break;
		}

		return $output;
	}

	/**
	 * Get the name from a WP user ID (used to store information on last editor of a table).
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 * @return string Nickname of the WP user with the $user_id.
	 */
	public static function get_user_display_name( int $user_id ): string {
		$user = get_userdata( $user_id );
		/* translators: %s: Label for unknown user */
		return $user->display_name ?? sprintf( '<em>%s</em>', __( 'unknown', 'tablepress' ) );
	}

	/**
	 * Sanitizes a CSS class to ensure it only contains valid characters.
	 *
	 * Strips the string down to A-Z, a-z, 0-9, :, _, -.
	 * This is an extension to WP's `sanitize_html_class()`, to also allow `:` which are used in some CSS frameworks.
	 *
	 * @since 1.11.0
	 *
	 * @param string $css_class The CSS class name to be sanitized.
	 * @return string The sanitized CSS class.
	 */
	public static function sanitize_css_class( string $css_class ): string {
		// Strip out any %-encoded octets.
		$sanitized_css_class = (string) preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $css_class );
		// Limit to A-Z, a-z, 0-9, ':', '_', and '-'.
		$sanitized_css_class = (string) preg_replace( '/[^A-Za-z0-9:_-]/', '', $sanitized_css_class );
		return $sanitized_css_class;
	}

	/**
	 * Extracts the top-level keys from a JavaScript object string.
	 *
	 * This function is used to extract the keys of the "Custom Commands" JavaScript object string, to check for overrides.
	 * It covers most cases, like normal object properties with and without quotes, shorthand properties, and shorthand methods,
	 * and also ignores single-line and multi-line comments.
	 * It does not cover all possible JavaScript syntax (like template literals, special characters, ...),
	 * but should be sufficient for the use case.
	 *
	 * @since 3.0.0
	 *
	 * @param string $js_object_string A JavaScript object as a string.
	 * @return string[] Array of top-level keys of the object.
	 */
	public static function extract_keys_from_js_object_string( string $js_object_string ): array {
		$object_keys = array();
		$length = strlen( $js_object_string );
		$depth = 0;
		$key_expected = true;
		$in_quotes = false;
		$quote_char = '';
		$in_function_declaration = false;
		$in_single_line_comment = false;
		$in_multi_line_comment = false;
		$object_key = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $js_object_string[ $i ];

			// Skip parsing single-line comments.
			if ( $in_single_line_comment ) {
				if ( "\n" === $char ) {
					$in_single_line_comment = false;
				}
				continue;
			} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
				if ( '/' === $char && $i + 1 < $length && '/' === $js_object_string[ $i + 1 ] ) {
					$in_single_line_comment = true;
					++$i; // Skip the second '/'.
					continue;
				}
			}

			// Skip parsing multi-line comments.
			if ( $in_multi_line_comment ) {
				if ( '*' === $char && $i + 1 < $length && '/' === $js_object_string[ $i + 1 ] ) {
					$in_multi_line_comment = false;
					++$i; // Skip the '/' that ends the multi-line comment.
				}
				continue;
			} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
				if ( '/' === $char && $i + 1 < $length && '*' === $js_object_string[ $i + 1 ] ) {
					$in_multi_line_comment = true;
					++$i; // Skip the '*'.
					continue;
				}
			}

			// Skip parsing while inside a quoted string.
			if ( $in_quotes ) {
				if ( $quote_char === $char ) {
					$in_quotes = false;
				}
				continue;
			} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
				if ( '"' === $char || "'" === $char ) {
					$in_quotes = true;
					$quote_char = $char;
					continue;
				}
			}

			/*
			 * Skip parsing while inside a `function abc( ... )` declaration string.
			 * The `$key_expected` check limits search the "function" string to object values.
			 * The check for the plain `f` reduces expensive `substr()` calls.
			 */
			if ( ! $key_expected ) {
				if ( $in_function_declaration ) {
					if ( ')' === $char ) {
						$in_function_declaration = false;
					}
					continue;
				} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
					if ( 'f' === $char && 'function' === substr( $js_object_string, $i, 8 ) ) {
						$in_function_declaration = true;
						$i += 7; // Skip the rest of the "function" string.
						continue;
					}
				}
			}

			// Handle object depth, so that most parsing can be limited to the top level.
			if ( '{' === $char || '[' === $char ) {
				++$depth;
			}

			// Extract only keys at the top level.
			if ( 1 === $depth ) {
				if ( $key_expected ) {
					if ( ':' === $char ) {
						// Check for normal keys, with value after :.

						// Go backwards to find the start of the key.
						$j = $i - 1;
						while ( $j >= 0 && preg_match( '/\s/', $js_object_string[ $j ] ) ) {
							--$j;
						}
						$key_end = $j; // Position of the last character of the key (potentially with quote).
						if ( '"' === $js_object_string[ $j ] || "'" === $js_object_string[ $j ] ) {
							// Quoted key.
							$quote_char = $js_object_string[ $j ];
							--$j;
							while ( $j >= 0 && $quote_char !== $js_object_string[ $j ] ) {
								--$j;
							}
							$key_start = $j + 1;
						} else {
							// Unquoted key.
							while ( $j >= 0 && preg_match( '/[\w]/', $js_object_string[ $j ] ) ) {
								--$j;
							}
							$key_start = $j + 1;
						}
						$object_key = substr( $js_object_string, $key_start, $key_end - $key_start + 1 );
						$object_key = trim( $object_key, "\"'" );
						if ( '' !== $object_key && ! in_array( $object_key, $object_keys, true ) ) {
							$object_keys[] = $object_key;
						}
						$key_expected = false;
					} elseif ( ( ',' === $char || '}' === $char ) ) { // The `}` case is for the last key.
						// Check for shorthand properties (which must be unquoted).

						// Go backwards to find the start of the shorthand key.
						$j = $i - 1;
						while ( $j >= 0 && preg_match( '/\s/', $js_object_string[ $j ] ) ) {
							--$j;
						}
						$key_end = $j; // Position of the last character of the key (without a quote).
						while ( $j >= 0 && preg_match( '/[\w]/', $js_object_string[ $j ] ) ) {
							--$j;
						}
						$key_start = $j + 1;
						$object_key = substr( $js_object_string, $key_start, $key_end - $key_start + 1 );
						if ( '' !== $object_key && ! in_array( $object_key, $object_keys, true ) ) {
							$object_keys[] = $object_key;
						}
					} elseif ( '(' === $char ) {
						// Detect shorthand method definitions.

						// Go back to find the start of the method name.
						$j = $i - 1;
						while ( $j >= 0 && preg_match( '/\s/', $js_object_string[ $j ] ) ) {
							--$j;
						}
						$key_end = $j;
						while ( $j >= 0 && preg_match( '/[\w]/', $js_object_string[ $j ] ) ) {
							--$j;
						}
						$key_start = $j + 1;
						$object_key = substr( $js_object_string, $key_start, $key_end - $key_start + 1 );
						if ( '' !== $object_key && ! in_array( $object_key, $object_keys, true ) ) {
							$object_keys[] = $object_key;
						}
					}
				}

				// Reset the "key expected" flag after a comma or closing brace.
				if ( ',' === $char || '}' === $char ) {
					$key_expected = true;
				}
			}

			// Handle object depth.
			if ( '}' === $char || ']' === $char ) {
				--$depth;
			}
		}

		return $object_keys;
	}

	/**
	 * Converts old DataTables 1.x CSS classes and parameters to the DataTables 2 variants.
	 *
	 * This function is used to modernize "Custom CSS" and "Custom Commands" for compatibility with DataTables 2.x.
	 * It probably does not catch all possible cases.
	 *
	 * @since 3.0.0
	 *
	 * @param string $code Code that contains DataTables 1.x CSS classes and parameters.
	 * @return string Updated code with DataTables 2.x CSS classes and parameters.
	 */
	public static function convert_datatables_api_data( string $code ): string {
		/**
		 * Mappings for DataTables 1.x CSS class or parameter to DataTables 2 variants.
		 * As this array is used in `strtr()`, it's pre-sorted for descending string length of the array keys.
		 */
		static $datatables_api_data_mappings = array(
			// CSS classes.
			'.tablepress thead .sorting:hover' => '.tablepress thead .dt-orderable-asc:hover,.tablepress thead .dt-orderable-desc:hover',
			'.tablepress thead .sorting_desc'  => '.tablepress thead .dt-ordering-desc',
			'.dataTables_filter label input'   => '.dt-container .dt-search input',
			'.tablepress thead .sorting_asc'   => '.tablepress thead .dt-ordering-asc',
			'.dataTables_scrollFootInner'      => '.dt-scroll-footInner',
			'.dataTables_scrollHeadInner'      => '.dt-scroll-headInner',
			'.tablepress thead .sorting'       => '.tablepress thead .dt-orderable-asc,.tablepress thead .dt-orderable-desc',
			'.dataTables_processing'           => '.dt-processing',
			'.dataTables_scrollBody'           => '.dt-scroll-body',
			'.dataTables_scrollFoot'           => '.dt-scroll-foot',
			'.dataTables_scrollHead'           => '.dt-scroll-head',
			'.dataTables_paginate'             => '.dt-paging',
			'.tablepress .even td'             => '.tablepress>:where(tbody.row-striping)>:nth-child(odd)>*',
			'.dataTables_wrapper'              => '.dt-container',
			'.tablepress .odd td'              => '.tablepress>:where(tbody.row-striping)>:nth-child(even)>*',
			'.dataTables_filter'               => '.dt-search',
			'.dataTables_length'               => '.dt-length',
			'.dataTables_scroll'               => '.dt-scroll',
			'.dataTables_empty'                => '.dt-empty',
			'.dataTables_info'                 => '.dt-info',
			'.paginate_button'                 => '.dt-paging-button',
			// DataTables API functions.
			'$.fn.dataTable.'                  => 'DataTable.',
		);
		$code = strtr( $code, $datatables_api_data_mappings );

		// HTML ID mappings, which were removed.
		if ( str_contains( $code, '#tablepress-' ) ) {
			$code = (string) preg_replace(
				array(
					'/#tablepress-([A-Za-z1-9_-]|[A-Za-z0-9_-]{2,})_paginate/',
					'/#tablepress-([A-Za-z1-9_-]|[A-Za-z0-9_-]{2,})_filter/',
					'/#tablepress-([A-Za-z1-9_-]|[A-Za-z0-9_-]{2,})_length/',
					'/#tablepress-([A-Za-z1-9_-]|[A-Za-z0-9_-]{2,})_info/',
				),
				array(
					'#tablepress-$1_wrapper .dt-paging',
					'#tablepress-$1_wrapper .dt-search',
					'#tablepress-$1_wrapper .dt-length',
					'#tablepress-$1_wrapper .dt-info',
				),
				$code,
			);
		}

		return $code;
	}

	/**
	 * Retrieves all information of a WP_Error object as a string.
	 *
	 * @since 1.4.0
	 *
	 * @param WP_Error $wp_error A WP_Error object.
	 * @return string All error codes, messages, and data of the WP_Error.
	 */
	public static function get_wp_error_string( WP_Error $wp_error ): string {
		$error_strings = array();
		$error_codes = $wp_error->get_error_codes();
		// Reverse order to get latest errors first.
		$error_codes = array_reverse( $error_codes );
		foreach ( $error_codes as $error_code ) {
			$error_strings[ $error_code ] = $error_code;
			$error_messages = $wp_error->get_error_messages( $error_code );
			$error_messages = implode( ', ', $error_messages );
			if ( ! empty( $error_messages ) ) {
				$error_strings[ $error_code ] .= " ({$error_messages})";
			}
			$error_data = $wp_error->get_error_data( $error_code );
			if ( is_string( $error_data ) ) {
				$error_strings[ $error_code ] .= " [{$error_data}]";
			} elseif ( is_array( $error_data ) ) {
				foreach ( $error_data as $key => $value ) {
					$error_data[ $key ] = "{$key}: {$value}";
				}
				$error_data = implode( ', ', $error_data );
				$error_strings[ $error_code ] .= " [{$error_data}]";
			}
		}
		return implode( ";\n", $error_strings );
	}

	/**
	 * Generate the action URL, to be used as a link within the plugin (e.g. in the submenu navigation or List of Tables).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $params    Optional. Parameters to form the query string of the URL.
	 * @param bool                 $add_nonce Optional. Whether the URL shall be nonced by WordPress.
	 * @param string               $target    Optional. Target File, e.g. "admin-post.php" for POST requests.
	 * @return string The URL for the given parameters (already run through esc_url() with $add_nonce === true!).
	 */
	public static function url( array $params = array(), bool $add_nonce = false, string $target = '' ): string {
		// Default action is "list", if no action given.
		if ( ! isset( $params['action'] ) ) {
			$params['action'] = 'list';
		}
		$nonce_action = $params['action'];

		if ( '' !== $target ) {
			$params['action'] = "tablepress_{$params['action']}";
		} else {
			$params['page'] = 'tablepress';
			// Top-level parent page needs special treatment for better action strings.
			if ( self::$controller->is_top_level_page ) {
				$target = 'admin.php';
				if ( ! in_array( $params['action'], array( 'list', 'edit' ), true ) ) {
					$params['page'] = "tablepress_{$params['action']}";
				}
				if ( ! in_array( $params['action'], array( 'edit' ), true ) ) {
					$params['action'] = false;
				}
			} else {
				$target = self::$controller->parent_page;
			}
		}

		// $default_params also determines the order of the values in the query string.
		$default_params = array(
			'page'   => false,
			'action' => false,
			'item'   => false,
		);
		$params = array_merge( $default_params, $params );

		$url = add_query_arg( $params, admin_url( $target ) );
		if ( $add_nonce ) {
			$url = wp_nonce_url( $url, self::nonce( $nonce_action, $params['item'] ) ); // wp_nonce_url() does esc_html().
		}
		return $url;
	}

	/**
	 * Create a redirect URL from the $target_parameters and redirect the user.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $params    Optional. Parameters from which the target URL is constructed.
	 * @param bool                 $add_nonce Optional. Whether the URL shall be nonced by WordPress.
	 */
	public static function redirect( array $params = array(), bool $add_nonce = false ): void {
		$redirect = self::url( $params );
		if ( $add_nonce ) {
			if ( ! isset( $params['item'] ) ) {
				$params['item'] = false;
			}
			// Don't use wp_nonce_url(), as that uses esc_html().
			$redirect = add_query_arg( '_wpnonce', wp_create_nonce( self::nonce( $params['action'], $params['item'] ) ), $redirect );
		}
		wp_redirect( $redirect );
		exit;
	}

	/**
	 * Determines the editor that the site uses, so that certain text and input fields referring to Shortcodes can be displayed or not.
	 *
	 * @since 3.1.0
	 *
	 * @return string The editor that the site uses, either "block", "elementor", or "other".
	 */
	public static function site_used_editor(): string {
		if ( is_plugin_active( 'elementor/elementor.php' ) ) {
			return 'elementor';
		}

		// Checking for Elementor is not needed anymore in this condition.
		$site_uses_block_editor = use_block_editor_for_post_type( 'post' )
			&& ! is_plugin_active( 'classic-editor/classic-editor.php' )
			&& ! is_plugin_active( 'classic-editor-addon/classic-editor-addon.php' )
			&& ! is_plugin_active( 'siteorigin-panels/siteorigin-panels.php' )
			&& ! is_plugin_active( 'beaver-builder-lite-version/fl-builder.php' );
		/**
		 * Filters the outcome of the check whether the site uses the block editor.
		 *
		 * This can be used when certain conditions (e.g. new site builders) are not (yet) accounted for.
		 *
		 * @since 2.0.1
		 *
		 * @param bool $site_uses_block_editor True if the site uses the block editor, false otherwise.
		 */
		$site_uses_block_editor = (bool) apply_filters( 'tablepress_site_uses_block_editor', $site_uses_block_editor );
		if ( $site_uses_block_editor ) {
			return 'block';
		}

		return 'other';
	}

	/**
	 * Initializes the list of TablePress premium modules.
	 *
	 * @since 2.1.0
	 */
	public static function init_modules(): void {
		if ( ! empty( self::$modules ) ) {
			return;
		}

		self::$modules = array(
			'advanced-access-rights'              => array(
				'name'                 => __( 'Advanced Access Rights', 'tablepress' ),
				'description'          => __( 'Restrict access to individual tables for individual users.', 'tablepress' ),
				'category'             => 'backend',
				'class'                => 'TablePress_Module_Advanced_Access_Rights',
				'incompatible_classes' => array( 'TablePress_Advanced_Access_Rights_Controller' ),
				'minimum_plan'         => 'max',
				'default_active'       => false,
			),
			'automatic-periodic-table-import'     => array(
				'name'                 => __( 'Automatic Periodic Table Import', 'tablepress' ),
				'description'          => __( 'Periodically update tables from a configured import source.', 'tablepress' ),
				'category'             => 'backend',
				'class'                => 'TablePress_Module_Automatic_Periodic_Table_Import',
				'incompatible_classes' => array( 'TablePress_Table_Auto_Update' ),
				'minimum_plan'         => 'max',
				'default_active'       => true,
			),
			'automatic-table-export'              => array(
				'name'                 => __( 'Automatic Table Export', 'tablepress' ),
				'description'          => __( 'Export and save tables to files on the server after they were modified.', 'tablepress' ),
				'category'             => 'backend',
				'class'                => 'TablePress_Module_Automatic_Table_Export',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'cell-highlighting'                   => array(
				'name'                 => __( 'Cell Highlighting', 'tablepress' ),
				'description'          => __( 'Add CSS classes to cells for highlighting based on their content.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_Cell_Highlighting',
				'incompatible_classes' => array( 'TablePress_Cell_Highlighting' ),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'column-order'                        => array(
				'name'                 => __( 'Column Order', 'tablepress' ),
				'description'          => __( 'Order the columns in different ways when a table is shown.', 'tablepress' ),
				'category'             => 'data-management',
				'class'                => 'TablePress_Module_Column_Order',
				'incompatible_classes' => array( 'TablePress_Column_Order' ),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-advanced-loading'         => array(
				'name'                 => __( 'Advanced Loading', 'tablepress' ),
				'description'          => __( 'Load the table data from a JSON array for faster loading.', 'tablepress' ),
				'category'             => 'backend',
				'class'                => 'TablePress_Module_DataTables_Advanced_Loading',
				'incompatible_classes' => array( 'TablePress_DataTables_Advanced_Loading' ),
				'minimum_plan'         => 'max',
				'default_active'       => false,
			),
			'datatables-alphabetsearch'           => array(
				'name'                 => __( 'Alphabet Search', 'tablepress' ),
				'description'          => __( 'Show Alphabet buttons above the table to filter rows by their first letter.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_Alphabetsearch',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-auto-filter'              => array(
				'name'                 => __( 'Automatic Filter', 'tablepress' ),
				'description'          => __( 'Pre-filter a table when it is shown.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_Auto_Filter',
				'incompatible_classes' => array( 'TablePress_DataTables_Auto_Filter' ),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-buttons'                  => array(
				'name'                 => __( 'User Action Buttons', 'tablepress' ),
				'description'          => __( 'Add buttons for downloading, copying, printing, and changing column visibility of tables.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_DataTables_Buttons',
				'incompatible_classes' => array( 'TablePress_DataTables_Buttons' ),
				'minimum_plan'         => 'pro',
				'default_active'       => true,
			),
			'datatables-columnfilterwidgets'      => array(
				'name'                 => __( 'Column Filter Dropdowns', 'tablepress' ),
				'description'          => __( 'Add a search dropdown for each column above the table.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_ColumnFilterWidgets',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => true,
			),
			'datatables-column-filter'            => array(
				'name'                 => __( 'Individual Column Filtering', 'tablepress' ),
				'description'          => __( 'Add a search field for each column to the table head or foot row.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_Column_Filter',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-counter-column'           => array(
				'name'                 => __( 'Index Column', 'tablepress' ),
				'description'          => __( 'Make the first column an index or counter column with the row position.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_DataTables_Counter_Column',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-fixedheader-fixedcolumns' => array(
				'name'                 => __( 'Fixed Rows and Columns', 'tablepress' ),
				'description'          => __( 'Fix the header and footer row and the first and last column when scrolling the table.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_DataTables_FixedHeader_FixedColumns',
				'incompatible_classes' => array(
					'TablePress_DataTables_FixedHeader',
					'TablePress_DataTables_FixedColumns',
				),
				'minimum_plan'         => 'pro',
				'default_active'       => true,
			),
			'datatables-layout'                   => array(
				'name'                 => __( 'Table Layout', 'tablepress' ),
				'description'          => __( 'Customize the layout and position of features around a table.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_DataTables_Layout',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => true,
			),
			'datatables-fuzzysearch'              => array(
				'name'                 => __( 'Fuzzy Search', 'tablepress' ),
				'description'          => __( 'Let the search account for spelling mistakes and typos and find similar matches.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_FuzzySearch',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'max',
				'default_active'       => false,
			),
			'datatables-inverted-filter'          => array(
				'name'                 => __( 'Inverted Filtering', 'tablepress' ),
				'description'          => __( 'Turn the filtering into a search and hide the table if no search term is entered.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_Inverted_Filter',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'max',
				'default_active'       => false,
			),
			'datatables-pagination'               => array(
				'name'                 => __( 'Advanced Pagination Settings', 'tablepress' ),
				'description'          => __( 'Customize the pagination settings of the table.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_DataTables_Pagination',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-rowgroup'                 => array(
				'name'                 => __( 'Row Grouping', 'tablepress' ),
				'description'          => __( 'Group table rows by a common keyword, category, or title.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_DataTables_RowGroup',
				'incompatible_classes' => array( 'TablePress_DataTables_RowGroup' ),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-searchbuilder'            => array(
				'name'                 => __( 'Custom Search Builder', 'tablepress' ),
				'description'          => __( 'Show a search builder interface for filtering from groups and using conditions.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_SearchBuilder',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'max',
				'default_active'       => false,
			),
			'datatables-searchhighlight'          => array(
				'name'                 => __( 'Search Highlighting', 'tablepress' ),
				'description'          => __( 'Highlight found search terms in the table.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_SearchHighlight',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-searchpanes'              => array(
				'name'                 => __( 'Search Panes', 'tablepress' ),
				'description'          => __( 'Show panes for filtering the columns.', 'tablepress' ),
				'category'             => 'search-filter',
				'class'                => 'TablePress_Module_DataTables_SearchPanes',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'datatables-serverside-processing'    => array(
				'name'                 => __( 'Server-side Processing', 'tablepress' ),
				'description'          => __( 'Process sorting, filtering, and pagination on the server for faster loading of large tables.', 'tablepress' ),
				'category'             => 'backend',
				'class'                => 'TablePress_Module_DataTables_ServerSide_Processing',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'max',
				'default_active'       => true,
			),
			'default-style-customizer'            => array(
				'name'                 => __( 'Default Style Customizer', 'tablepress' ),
				'description'          => __( 'Change the default styling of your tables in the visual style customizer.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_Default_Style_Customizer',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'pro',
				'default_active'       => true,
			),
			'email-notifications'                 => array(
				'name'                 => __( 'Email Notifications', 'tablepress' ),
				'description'          => __( 'Get email notifications when certain actions are performed on tables.', 'tablepress' ),
				'category'             => 'backend',
				'class'                => 'TablePress_Module_Email_Notifications',
				'incompatible_classes' => array(),
				'minimum_plan'         => 'max',
				'default_active'       => false,
			),
			'responsive-tables'                   => array(
				'name'                 => __( 'Responsive Tables', 'tablepress' ),
				'description'          => __( 'Make your tables look good on different screen sizes.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_Responsive_Tables',
				'incompatible_classes' => array( 'TablePress_Responsive_Tables' ),
				'minimum_plan'         => 'pro',
				'default_active'       => true,
			),
			'rest-api'                            => array(
				'name'                 => __( 'REST API', 'tablepress' ),
				'description'          => __( 'Read table data via the WordPress REST API, e.g. in external apps.', 'tablepress' ),
				'category'             => 'backend',
				'class'                => 'TablePress_Module_REST_API',
				'incompatible_classes' => array( 'TablePress_REST_API_Controller' ),
				'minimum_plan'         => 'max',
				'default_active'       => false,
			),
			'row-filtering'                       => array(
				'name'                 => __( 'Row Filtering', 'tablepress' ),
				'description'          => __( 'Show only table rows that contain defined keywords.', 'tablepress' ),
				'category'             => 'data-management',
				'class'                => 'TablePress_Module_Row_Filtering',
				'incompatible_classes' => array( 'TablePress_Row_Filter' ),
				'minimum_plan'         => 'pro',
				'default_active'       => true,
			),
			'row-highlighting'                    => array(
				'name'                 => __( 'Row Highlighting', 'tablepress' ),
				'description'          => __( 'Add CSS classes to rows for highlighting based on their content.', 'tablepress' ),
				'category'             => 'frontend',
				'class'                => 'TablePress_Module_Row_Highlighting',
				'incompatible_classes' => array( 'TablePress_Row_Highlighting' ),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
			'row-order'                           => array(
				'name'                 => __( 'Row Order', 'tablepress' ),
				'description'          => __( 'Order the rows in different ways when a table is shown.', 'tablepress' ),
				'category'             => 'data-management',
				'class'                => 'TablePress_Module_Row_Order',
				'incompatible_classes' => array( 'TablePress_Row_Order' ),
				'minimum_plan'         => 'pro',
				'default_active'       => false,
			),
		);
	}

} // class TablePress
