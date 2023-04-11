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
	const version = '2.1.1'; // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

	/**
	 * TablePress internal plugin version ("options scheme" version).
	 *
	 * Increases whenever the scheme for the plugin options changes, or on a plugin update.
	 *
	 * @since 1.0.0
	 * @const int
	 */
	const db_version = 57; // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

	/**
	 * TablePress "table scheme" (data format structure) version.
	 *
	 * Increases whenever the scheme for a $table changes,
	 * used to be able to update plugin options and table scheme independently.
	 *
	 * @since 1.0.0
	 * @const int
	 */
	const table_scheme_version = 3; // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase

	/**
	 * Instance of the Options Model.
	 *
	 * @since 1.3.0
	 * @var TablePress_Options_Model
	 */
	public static $model_options;

	/**
	 * Instance of the Table Model.
	 *
	 * @since 1.3.0
	 * @var TablePress_Table_Model
	 */
	public static $model_table;

	/**
	 * Instance of the controller.
	 *
	 * @since 1.0.0
	 * @var TablePress_*_Controller
	 */
	public static $controller;

	/**
	 * Name of the Shortcode to show a TablePress table.
	 *
	 * Should only be modified through the filter hook 'tablepress_table_shortcode'.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $shortcode = 'table';

	/**
	 * Name of the Shortcode to show extra information of a TablePress table.
	 *
	 * Should only be modified through the filter hook 'tablepress_table_info_shortcode'.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $shortcode_info = 'table-info';

	/**
	 * List of TablePress premium modules.
	 *
	 * @since 2.1.0
	 * @var array
	 */
	public static $modules = array();

	/**
	 * Start-up TablePress (run on WordPress "init") and load the controller for the current state.
	 *
	 * @since 1.0.0
	 */
	public static function run() {
		/**
		 * Fires before TablePress is loaded.
		 *
		 * The `tablepress_loaded` action hook might be a better choice in most situations, as TablePress options will then be available.
		 *
		 * @since 1.0.0
		 */
		do_action( 'tablepress_run' );

		// Check if minimum requirements are fulfilled, currently WordPress 5.8.
		include ABSPATH . WPINC . '/version.php'; // Include an unmodified $wp_version.
		if ( version_compare( str_replace( '-src', '', $wp_version ), '5.8', '<' ) ) {
			// Show error notice to admins, if WP is not installed in the minimum required version, in which case TablePress will not work.
			if ( current_user_can( 'update_plugins' ) ) {
				add_action( 'admin_notices', array( 'TablePress', 'show_minimum_requirements_error_notice' ) );
			}
			// And exit TablePress.
			return;
		}

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
				$controller .= '_ajax';
			}
			self::load_controller( $controller );
		}
		// Load the frontend controller in all scenarios, so that Shortcode render functions are always available.
		self::$controller = self::load_controller( 'frontend' );

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
	public static function load_file( $file, $folder ) {
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
	 * @param string $class_name Name of the class.
	 * @param string $file       Name of the PHP file with the class.
	 * @param string $folder     Name of the folder with $class_name's $file.
	 * @param mixed  $params     Optional. Parameters that are passed to the constructor of $class_name.
	 * @return object Initialized instance of the class.
	 */
	public static function load_class( $class_name, $file, $folder, $params = null ) {
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
	public static function load_model( $model ) {
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
	 * @param string $view Name of the view to load.
	 * @param array  $data Optional. Parameters/PHP variables that shall be available to the view.
	 * @return object Instance of the initialized view, already set up, just needs to be rendered.
	 */
	public static function load_view( $view, array $data = array() ) {
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
	public static function load_controller( $controller ) {
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
	 * @param string      $action Action for which the nonce is needed.
	 * @param string|bool $item   Optional. Item for which the action will be performed, like "table".
	 * @return string The resulting nonce string.
	 */
	public static function nonce( $action, $item = false ) {
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
	 * @param string      $action    Action for which the nonce should be checked.
	 * @param string|bool $item      Optional. Item for which the action should be performed, like "table".
	 * @param string      $query_arg Optional. Name of the nonce query string argument in $_POST.
	 * @param bool        $ajax Whether the nonce comes from an AJAX request.
	 */
	public static function check_nonce( $action, $item = false, $query_arg = '_wpnonce', $ajax = false ) {
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
	public static function letter_to_number( $column ) {
		$column = strtoupper( $column );
		$count = strlen( $column );
		$number = 0;
		for ( $i = 0; $i < $count; $i++ ) {
			$number += ( ord( $column[ $count - 1 - $i ] ) - 64 ) * pow( 26, $i );
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
	public static function number_to_letter( $number ) {
		$column = '';
		while ( $number > 0 ) {
			$column = chr( 65 + ( ( $number - 1 ) % 26 ) ) . $column;
			$number = floor( ( $number - 1 ) / 26 );
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
	public static function format_datetime( $datetime_string, $separator_or_format = ' ' ) {
		$timezone = wp_timezone();
		$datetime = date_create( $datetime_string, $timezone );
		$timestamp = $datetime->getTimestamp();

		switch ( $separator_or_format ) {
			case ' ':
			case '<br />':
				$date = wp_date( get_option( 'date_format' ), $timestamp, $timezone );
				$time = wp_date( get_option( 'time_format' ), $timestamp, $timezone );
				$output = "{$date}{$separator_or_format}{$time}";
				break;
			default:
				$output = wp_date( $separator_or_format, $timestamp, $timezone );
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
	public static function get_user_display_name( $user_id ) {
		$user = get_userdata( $user_id );
		return ( isset( $user->display_name ) ) ? $user->display_name : sprintf( '<em>%s</em>', __( 'unknown', 'tablepress' ) );
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
	public static function sanitize_css_class( $css_class ) {
		// Strip out any %-encoded octets.
		$sanitized_css_class = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $css_class );
		// Limit to A-Z, a-z, 0-9, ':', '_', and '-'.
		$sanitized_css_class = preg_replace( '/[^A-Za-z0-9:_-]/', '', $sanitized_css_class );
		return $sanitized_css_class;
	}

	/**
	 * Retrieves all information of a WP_Error object as a string.
	 *
	 * @since 1.4.0
	 *
	 * @param WP_Error $wp_error A WP_Error object.
	 * @return string All error codes, messages, and data of the WP_Error.
	 */
	public static function get_wp_error_string( $wp_error ) {
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
			if ( ! is_null( $error_data ) ) {
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
	 * @param array  $params    Optional. Parameters to form the query string of the URL.
	 * @param bool   $add_nonce Optional. Whether the URL shall be nonced by WordPress.
	 * @param string $target    Optional. Target File, e.g. "admin-post.php" for POST requests.
	 * @return string The URL for the given parameters (already run through esc_url() with $add_nonce === true!).
	 */
	public static function url( array $params = array(), $add_nonce = false, $target = '' ) {
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
	 * @param array $params    Optional. Parameters from which the target URL is constructed.
	 * @param bool  $add_nonce Optional. Whether the URL shall be nonced by WordPress.
	 */
	public static function redirect( array $params = array(), $add_nonce = false ) {
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
	 * Show an error notice to admins, if TablePress's minimum requirements are not reached.
	 *
	 * @since 1.0.0
	 */
	public static function show_minimum_requirements_error_notice() {
		// Message is not translated as it is shown on every admin screen, for which we don't want to load translations.
		echo '<div class="notice notice-error form-invalid"><p>' .
			'<strong>Attention:</strong> ' .
			'The installed version of WordPress is too old for the TablePress plugin! TablePress requires an up-to-date version! <strong>Please <a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">update your WordPress installation</a></strong>!' .
			"</p></div>\n";
	}

	/**
	 * Determines whether the site uses the block editor, so that certain text and input fields referring to Shortcodes can be displayed or not.
	 *
	 * @since 2.0.1
	 *
	 * @return bool True if the site uses the block editor, false otherwise.
	 */
	public static function site_uses_block_editor() {
		$site_uses_block_editor = use_block_editor_for_post_type( 'post' )
			&& ! is_plugin_active( 'beaver-builder-lite-version/fl-builder.php' )
			&& ! is_plugin_active( 'classic-editor/classic-editor.php' )
			&& ! is_plugin_active( 'classic-editor-addon/classic-editor-addon.php' )
			&& ! is_plugin_active( 'elementor/elementor.php' )
			&& ! is_plugin_active( 'siteorigin-panels/siteorigin-panels.php' );

		/**
		 * Filters the outcome of the check whether the site uses the block editor.
		 *
		 * This can be used when certain conditions (e.g. new site builders) are not (yet) accounted for.
		 *
		 * @since 2.0.1
		 *
		 * @param bool $site_uses_block_editor True if the site uses the block editor, false otherwise.
		 */
		$site_uses_block_editor = apply_filters( 'tablepress_site_uses_block_editor', $site_uses_block_editor );

		return $site_uses_block_editor;
	}

	/**
	 * Initializes the list of TablePress premium modules.
	 *
	 * @since 2.1.0
	 */
	public static function init_modules() {
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
				'name'                 => __( 'Buttons', 'tablepress' ),
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
				'name'                 => __( 'Counter Column', 'tablepress' ),
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
