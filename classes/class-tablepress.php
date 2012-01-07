<?php
/**
 * TablePress Class
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress class
 *
 * @since 1.0.0
 */
abstract class TablePress {

	/**
	 * @const string TablePress version
	 *
	 * @since 1.0.0
	 */
	const version = '1.0-alpha';

	/**
	 * @const int TablePress "data scheme" version
	 *
	 * @since 1.0.0
	 */
	const db_version = 6;

	/**
	 * @var object Instance of the controller object
	 *
	 * @since 1.0.0
	 */
	public static $controller;

	/**
	 * Start-up TablePress (run on WordPress "init") and load the controller for the current state
	 *
	 * @since 1.0.0
	 * @uses load_controller()
	 */
	public static function run() {
		do_action( 'tablepress_run' );
		if ( is_admin() ) {
			$controller = 'admin';
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
				$controller .= '_ajax';
		} else {
			$controller = 'frontend';
		}
		self::$controller = self::load_controller( $controller );
	}

	/**
	 * Load a file with require_once(), after running it through a filter
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Name of the PHP file with the class
	 * @param string $folder Name of the folder with $class's $file
	 */
	public static function load_file( $file, $folder ) {
		$full_path = TABLEPRESS_ABSPATH . $folder . '/' . $file;
		$full_path = apply_filters( 'tablepress_load_file_full_path', $full_path, $file, $folder );
		if ( $full_path )
			require_once $full_path;
	}

	/**
	 * Create a new instance of the $class, which is stored in $file in the $folder subfolder
	 * of the plugin's directory
	 *
	 * @since 1.0.0
	 *
	 * @param string $class Name of the class
	 * @param string $file Name of the PHP file with the class
	 * @param string $folder Name of the folder with $class's $file
	 * @return object Initialized instance of the class
	 */
	public static function load_class( $class, $file, $folder ) {
		self::load_file( $file, $folder );
		$class = apply_filters( 'tablepress_load_class_name', $class );
		$the_class = new $class();
		return $the_class;
	}

	/**
	 * Create a new instance of the $model, which is stored in the "models" subfolder
	 *
	 * @since 1.0.0
	 * @uses load_class()
	 *
	 * @param string $model Name of the model
	 * @return object Instance of the initialized model
	 */
	public static function load_model( $model ) {
		self::load_file( 'class-model.php', 'classes' ); // Model Base Class
		$the_model = self::load_class( "TablePress_{$model}_Model", "model-{$model}.php", 'models' );
		return $the_model;
	}

	/**
	 * Create a new instance of the $view, which is stored in the "views" subfolder, and set it up with $data
	 *
	 * @since 1.0.0
 	 * @uses load_class()
	 *
	 * @param string $view Name of the view to load
	 * @param array $data (optional) Parameters/PHP variables that shall be available to the view
	 * @return object Instance of the initialized view, already set up, just needs to be render()ed
	 */
	public static function load_view( $view, $data = array() ) {
		self::load_file( 'class-view.php', 'classes' ); // View Base Class
		$the_view = self::load_class( "TablePress_{$view}_View", "view-{$view}.php", 'views' );
		$the_view->setup( $view, $data );
		return $the_view;
	}
	
	/**
	 * Create a new instance of the $controller, which is stored in the "controllers" subfolder
	 *
	 * @since 1.0.0
	 * @uses load_class()
	 *
	 * @param string $controller Name of the controller
	 * @return object Instance of the initialized controller
	 */
	public static function load_controller( $controller ) {
		self::load_file( 'class-controller.php', 'classes' ); // Controller Base Class
		$the_controller = self::load_class( "TablePress_{$controller}_Controller", "controller-{$controller}.php", 'controllers' );
		return $the_controller;
	}

	/**
	 * Generate the complete nonce string, from the nonce base, the action and an item, e.g. tablepress_delete_table_3
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for which the nonce is needed
	 * @param string $item (optional) Item for which the action will be performed, like "table"
	 * @return string The resulting nonce string
	 */
	public static function nonce( $action, $item = false ) {
		$nonce = "tablepress_{$action}";
		if ( $item )
			$nonce .= "_{$item}";
		return $nonce;
	}

	/**
	 * Check whether a nonce string is valid
	 *
	 * @since 1.0.0
	 * @uses nonce()
	 *
	 * @param string $action Action for which the nonce should be checked
	 * @param string $item (optional) Item for which the action should be performed, like "table"
	 * @param bool $ajax Whether the nonce comes from an AJAX request
	 */
	public static function check_nonce( $action, $item = false, $ajax = false ) {
		$nonce_action = self::nonce( $action, $item );
		if ( $ajax )
			check_ajax_referer( $nonce_action );
		else
			check_admin_referer( $nonce_action );
	}

	/**
	 * Calculate the column index (number) of a column header string (example: A is 1, AA is 27, ...)
	 *
	 * For the opposite, @see number_to_letter()
	 *
	 * @since 1.0.0
	 *
	 * @param string $column Column string
	 * @return int $number Column number, 1-based
	 */
	function letter_to_number( $column ) {
		$column = strtoupper( $column );
		$count = strlen( $column );
		$number = 0;
		for ( $i = 0; $i < $count; $i++ ) {
			$number += ( ord( $column[ $count-1-$i ] ) - 64 ) * pow( 26, $i );
		}
		return $number;
	}

	/**
	 * "Calculate" the column header string of a column index (example: 2 is B, AB is 28, ...)
	 *
	 * For the opposite, @see letter_to_number()
	 *
	 * @since 1.0.0
	 *
	 * @param int $number Column number, 1-based
	 * @return string $column Column string
	 */
	function number_to_letter( $number ) {
		$column = '';
		while ( $number > 0 ) {
			$column = chr( 65 + ( ( $number-1) % 26 ) ) . $column;
			$number = floor( ($number-1) / 26 );
		}
		return $column;
	}

	/**
	 * Generate the action URL, to be used as a link within the plugin (e.g. in the submenu navigation or List of Tables)
	 *
	 * @since 1.0.0
	 *
	 * @param array $params (optional) Parameters to form the query string of the URL
	 * @param bool $add_nonce (optional) Whether the URL shall be nonced by WordPress
	 * @param string $target (optional) Target File, e.g. "admin-post.php" for POST requests
	 * @return string The URL for the given parameters
	 */
	public static function url( $params = array(), $add_nonce = false, $target = '' ) {

		// default action is "list", if no action given
		if ( ! isset( $params['action'] ) )
			$params['action'] = 'list';
		$nonce_action = $params['action'];

		if ( $target ) {
			$params['action'] = "tablepress_{$params['action']}";
		} else {
			$params['page'] = 'tablepress';
			// top-level parent page needs special treatment for better action strings
			if ( self::$controller->is_top_level_page ) {
				$target = 'admin.php';
				if ( in_array( $params['action'], array( 'add', 'import', 'export', 'options', 'about' ) ) )
					$params['page'] .= '_' . $params['action'];
				if ( in_array( $params['action'], array( 'list', 'add', 'import', 'export', 'options', 'about' ) ) )
					$params['action'] = false;
			} else {
				$target = self::$controller->parent_page;
			}
		}

		// $default_params also determines the order of the values in the query string
		$default_params = array(
			'page' => false,
			'action' => false,
			'item' => false
		);		
		$params = array_merge( $default_params, $params );

		$url = add_query_arg( $params, admin_url( $target ) );
		if ( $add_nonce )
			$url = wp_nonce_url( $url, self::nonce( $nonce_action, $params['item'] ) ); // wp_nonce_url() does esc_html()
		return $url;
	}

	/**
	 * Create a redirect URL from the $target_parameters and redirect the user
	 *
	 * @since 1.0.0
	 * @uses url()
	 *
	 * @param array $target_parameters (optional) Parameters from which the target URL is constructed
	 */
	public static function redirect( $target_parameters = array() ) {
		$redirect = self::url( $target_parameters );
		wp_redirect( $redirect );
		die();
	}

} // class TablePress