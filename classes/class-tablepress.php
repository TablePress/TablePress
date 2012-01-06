<?php
/**
 * TablePress Class
 *
 * @package TablePress
 * @author Tobias Bäthge
 * @since 1.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress class
 */
abstract class TablePress {

	/*
	 * @const string TablePress version
	 */
	const version = '1.0-alpha';

	/*
	 * @const int TablePress "data scheme" version
	 */
	const db_version = 2;

	/*
	 * @var object Instance of the controller object
	 */
	public static $controller;

	/*
	 * Start-up TablePress (run on WordPress "init") and load the controller for the current state
	 *
	 * @uses load_controller()
	 */
	public static function run() {
		$controller = ( is_admin() ) ? 'admin' : 'frontend'; // admin or frontend?
		//if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		//	$controller .= '_ajax'; // AJAX or regular request? (but only in admin)
		self::$controller = self::load_controller( $controller );
	}

	/**
	 * Create a new instance of the $class, which is stored in $file in the $folder subfolder
	 * of the plugin's directory
	 *
	 * @param string $class Name of the class
	 * @param string $file Name of the PHP file with the class
	 * @param string $folder Name of the folder with $class's $file
	 * @return object Initialized instance of the class
	 */
	public static function load_class( $class, $file, $folder ) {
		require_once ( TABLEPRESS_ABSPATH . $folder . '/' . $file );
		$the_class = new $class();
		return $the_class;
	}

	/**
	 * Create a new instance of the $model, which is stored in the "models" subfolder
	 *
	 * @param string $model Name of the model
	 * @return object Instance of the initialized model
	 * @uses load_class()
	 */
	public static function load_model( $model ) {
		require_once ( TABLEPRESS_ABSPATH . 'classes/class-model.php' );
		$the_model = self::load_class( "TablePress_{$model}_Model", "model-{$model}.php", 'models' );
		return $the_model;
	}

	/**
	 * Create a new instance of the $view, which is stored in the "views" subfolder, and set it up with $data
	 *
	 * @param string $view Name of the view to load
	 * @param array $data (optional) Parameters/PHP variables that shall be available to the view
	 * @return object Instance of the initialized view, already set up, just needs to be render()ed
	 * @uses load_class()
	 */
	public static function load_view( $view, $data = array() ) {
		require_once ( TABLEPRESS_ABSPATH . 'classes/class-view.php' );
		$the_view = self::load_class( "TablePress_{$view}_View", "view-{$view}.php", 'views' );
		$the_view->setup( $view, $data );
		return $the_view;
	}
	
	/**
	 * Create a new instance of the $controller, which is stored in the "controllers" subfolder
	 *
	 * @param string $controller Name of the controller
	 * @return object Instance of the initialized controller
	 * @uses load_class()
	 */
	public static function load_controller( $controller ) {
		require_once ( TABLEPRESS_ABSPATH . 'classes/class-controller.php' );
		$the_controller = self::load_class( "TablePress_{$controller}_Controller", "controller-{$controller}.php", 'controllers' );
		return $the_controller;
	}

	/**
	 * Generate the complete nonce string, from the nonce base, the action and an item, e.g. tablepress_delete_table
	 *
	 * @param string $action Action for which the nonce is needed
	 * @param string $item (optional) Item for which the action will be performed, like "table"
	 * @return string The resulting nonce string
	 */
	public static function nonce( $action, $item = false ) {
		$nonce = self::$controller->slug . '_' . $action;
		if ( $item )
			$nonce .= "_{$item}";
		return $nonce;
	}

	/**
	 * Check whether a nonce string is valid
	 *
	 * @param string $action Action for which the nonce should be checked
	 * @param string $item (optional) Item for which the action should be performed, like "table"
	 * @param bool $ajax Whether the nonce comes from an AJAX request
	 * @param bool|string false for default argument name or name to be used
	 * @uses nonce()
	 */
	public static function check_nonce( $action, $item = false, $ajax = false, $nonce_arg = false ) {
		// does this make sense???
		//if ( ! current_user_can( 'manage_options' ) )
		//	wp_die( __('Cheatin&#8217; uh?') );

		$nonce_action = self::nonce( $action, $item );
		if ( $ajax ) {
			check_ajax_referer( $nonce_action, $nonce_arg );
		} else {
			if ( ! $nonce_arg )
				$nonce_arg = '_wpnonce';
			check_admin_referer( $nonce_action, $nonce_arg );
		}
	}

	/*
	 * Calculate the column index (number) of a column header string (example: A is 1, AA is 27, ...)
	 *
	 * For the opposite, @see number_to_letter()
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

	/*
	 * "Calculate" the column header string of a column index (example: 2 is B, AB is 28, ...)
	 *
	 * For the opposite, @see letter_to_number()
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
	 * @param array $params (optional) Parameters to form the query string of the URL
	 * @param bool $add_nonce (optional) Whether the URL shall be nonced by WordPress
	 * @param bool $set_parent (optional) Target File, e.g. for POST requests
	 * @return string The action URL
	 */
	public static function url( $params = array(), $add_nonce = false, $set_parent = false ) {
		// if no target is set, use the current parent page
		if ( ! $set_parent ) {
			$parent = self::$controller->parent_page;
			$page_slug = self::$controller->slug;
		} else {
			$parent = $set_parent;
			$page_slug = false;
		}
		// default action is "list", if no action given
		$action = ( isset( $params['action'] ) ) ? $params['action'] : 'list';
		// top-level parent page needs special treatment for better action strings
		if ( ! $set_parent && self::$controller->is_top_level_page ) {
			$parent = 'admin.php';
			if ( in_array( $action, array( 'add', 'import', 'export', 'options', 'about' ) ) )
				$page_slug .= "_{$action}";
			if ( in_array( $action, array( 'list', 'add', 'import', 'export', 'options', 'about' ) ) )
				$action = false;
			unset( $params['action'] );
		}
		$default_params = array(
				'page' => $page_slug,
				'action' => $action,
				'item' => false
		);
		$url_params = array_merge( $default_params, $params );

		$action_url = add_query_arg( $url_params, admin_url( $parent ) );
		if ( $add_nonce )
			$action_url = wp_nonce_url( $action_url, self::nonce( $url_params['action'], $url_params['item'] ) );
		return $action_url;
	}

	/**
	 * Create a redirect URL from the $target_parameters and redirect the user
	 *
	 * @param array $target_parameters (optional) Parameters from which the target URL is constructed
	 * @uses url()
	 */
	public static function redirect( $target_parameters = array() ) {
		$redirect = self::url( $target_parameters );
		wp_redirect( $redirect );
		die();
	}

} // class TablePress