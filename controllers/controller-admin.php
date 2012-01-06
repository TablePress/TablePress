<?php
/**
 * Admin Controller for TablePress with the functionality for the non-AJAX backend
 *
 * @package TablePress
 * @subpackage Admin Controller
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin Controller class, extends Base Controller Class
 */
class TablePress_Admin_Controller extends TablePress_Controller {

	/**
	 * Page hooks (i.e. names) WordPress uses for the TablePress admin screens, populated in add_admin_menu_entry()
	 * @var array (of strings)
	 */
	private $page_hooks = array();

	/**
	 * @var array Actions that have a view and admin menu or nav tab menu entry
	 */
	private $view_actions = array();

	/**
	 * Initialize the Admin Controller, determine location the admin menu, set up actions
	 */
	public function __construct() {
		parent::__construct();
		//$this->model_table = TablePress::load_model( 'table' ); // could be moved, if solution for register_post_type on init is found

		add_action( 'admin_menu', array( &$this, 'add_admin_menu_entries' ) );
		add_action( 'admin_init', array( &$this, 'add_admin_actions' ) );
	}

	/**
	 * Add admin screens to the correct place in the admin menu
	 */
	public function add_admin_menu_entries() {
		// for all menu entries:
		$tablepress_page_title = $tablepress_menu_title = apply_filters( 'tablepress_admin_page_title', 'TablePress' );
		$min_access_cap = apply_filters( 'tablepress_min_access_cap', 'read' ); // make this a Plugin Option!
		$callback = array( &$this, 'show_admin_page' );

		if ( $this->is_top_level_page ) {
			$this->init_i18n_support(); // done here as translated strings for admin menu are needed
			$this->init_view_actions();
	
			$icon_url = plugins_url( 'admin/tablepress-icon-small.png', TABLEPRESS__FILE__ );	
			switch ( $this->parent_page ) {
				case 'top':
					$position = 3; // position of Dashboard + 1
					break;
				case 'middle':
					$position = ( ++$GLOBALS['_wp_last_object_menu'] );
					break;
				case 'bottom':
					$position = ( ++$GLOBALS['_wp_last_utility_menu'] );
					break;
			}
			add_menu_page( $tablepress_page_title, $tablepress_menu_title, $min_access_cap, $this->slug, $callback, $icon_url, $position );
			foreach ( $this->view_actions as $action => $entry ) {
				if ( ! $entry['show_entry'] )
					continue;
				$slug = $this->slug;
				if ( 'list' != $action )
					$slug .= '_' . $action;
				$this->page_hooks[] = add_submenu_page( $this->slug, sprintf( $entry['page_title'], $tablepress_menu_title ), $entry['admin_menu_title'], $entry['min_access_cap'], $slug, $callback );
			}
		} else {
			$this->page_hooks[] = add_submenu_page( $this->parent_page, $tablepress_page_title, $tablepress_menu_title, $min_access_cap, $this->slug, $callback );
		}
	}
	
	/**
	 * Set up handlers for user actions in the backend that exceed plain viewing
	 */
	public function add_admin_actions() {
		// register_activation_hook( TABLEPRESS__FILE__, array( &$this, 'plugin_activation_hook' ) );
		// register_deactivation_hook( TABLEPRESS__FILE__, array( &$this, 'plugin_deactivation_hook' ) );

		// register the callback being used if options of page have been submitted and needs to be processed
		$post_actions = array( 'options', 'debug' );// array( 'list', 'edit', 'add', 'options', 'debug' ); // list und debug nur temporary
		$get_actions = array( 'hide_message' );// array( 'delete_table', 'hide_message' ); // need special treatment regarding nonce checks
		foreach ( $post_actions as $action ) {
			add_action( "admin_post_{$this->slug}_{$action}", array( &$this, "handle_post_action_{$action}" ) );
		}
		foreach ( $get_actions as $action ) {
			add_action( "admin_post_{$this->slug}_{$action}", array( &$this, "handle_get_action_{$action}" ) );
		}

		// register callbacks to trigger load behavior for admin pages
		foreach ( $this->page_hooks as $page_hook ) {
			add_action( "load-{$page_hook}", array( &$this, 'load_admin_page' ) );
		}
		//add_action( 'load-plugins.php', array( &$this, 'plugin_notification' ) );
	}

	/**
	 * Prepare the rendering of an admin screen, by determining the current action, loading necessary data and initializing the view
	 */
	 public function load_admin_page() {
		// determine the action from either the GET parameter or a top-level admin menu entry screen ID
		if ( $this->is_top_level_page ) {
			$screen_id = get_current_screen()->id;
			// top-level menu entry: determining the action needs special treatment
			if ( 0 === strpos( $screen_id, 'toplevel_' ) )
				// actions that are top-level entries and have an action GET parameter
				$action = ( ! empty( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'debug' ) ) ) ? $_GET['action'] : 'list';
			else
				// actions that are top-level entries, but don't have an action GET parameter (action is after last _ in string)
				$action = substr( $screen_id, strrpos( $screen_id, '_') + 1 );
		} else {
			// sub menu entry: action is transported as a GET parameter
			$action = ( ! empty( $_GET['action'] ) ) ? $_GET['action'] : 'list';	
			$this->init_i18n_support(); // done here as for sub menu admin pages, this is the first time translated strings are needed
			$this->init_view_actions(); // for top-level menu entries, this has been done above, just like init_i18n_support()
		}

		// check if action is a supported action, and whether the user is allowed to access this screen
		if ( ! isset( $this->view_actions[ $action ] ) || ! current_user_can( $this->view_actions[ $action ]['min_access_cap'] ) )
			wp_die( __('You do not have sufficient permissions to access this page.') );

		// changes current screen ID and pagenow variable in JS, to enable automatic meta box JS handling
		set_current_screen( "{$this->slug}_{$action}" );

		// pre-define some table data
		$data = array(
			'action' => $action,
			'view_actions' => $this->view_actions,
			'message' => ( ! empty( $_GET['message'] ) ) ? $_GET['message'] : false
		);

		// depending on action, load more necessary data for the corresponding view
		switch ( $action ) {
			case 'list':
				//$data['tables'] = $this->model_table->load_all();
				//$data['tables_count'] = $this->model_table->count_tables();
				$data['messages']['first_visit'] = $this->model_options->get( 'message_first_visit' );
				$data['messages']['plugin_update'] = $this->model_options->get( 'message_plugin_update' );
				break;
			case 'options':
				$data['user_options']['parent_page'] = $this->parent_page;
				$data['user_options']['plugin_language'] = $this->model_options->get( 'plugin_language' ); //'en_US';
				$data['user_options']['available_plugin_languages'] = array( 'en_US' => __( 'English', 'tablepress' ), 'de_DE' => __( 'German', 'tablepress' ) );
				break;
			case 'debug':
				$data['debug']['plugin_options'] = json_encode( $this->model_options->get_plugin_options() );
				$data['debug']['user_options'] = json_encode( $this->model_options->get_user_options() );
				// $data['tables'] = $this->model_table->_debug_retrieve_tables();
				// $data['counts'] = $this->model_table->count_tables( false );
				break;	
		}
		/*
		// depending on action, load more necessary data for the corresponding view
		switch ( $action ) {
			case 'edit':
				if ( ! empty( $_GET['table_id'] ) ) {
					$data['table_id'] = (int)$_GET['table_id'];
					$data['table'] = $this->model_table->load( $data['table_id'] );
				} else {
					TablePress::redirect( array( 'action' => 'list', 'message' => 'error_no_table' ) );
				}
				break;
			case 'export':
				$data['tables'] = $this->model_table->load_all();
				$data['tables_count'] = $this->model_table->count_tables();
				if ( ! empty( $_GET['table_id'] ) ) {
					$data['table_id'] = (int)$_GET['table_id'];
					// this is actually done in the post_import handler function
					$data['table'] = $this->model_table->load( $data['table_id'] );
					$data['export_output'] = '<a href="http://test.com">ada</a>';
				} else {
					// just show empty export form
				}
				break;
			case 'import':
				$data['tables'] = $this->model_table->load_all();
				$data['tables_count'] = $this->model_table->count_tables();
				break;
		}
*/

		// prepare and initialize the view
		$this->view = TablePress::load_view( $action, $data );
	}

	/**
	 * Render the view that has been initialized in load_admin_page() (called by WordPress when the actual page content is needed)
	 */
	public function show_admin_page() {
		$this->view->render();
	}

    /**
     * Initialize i18n support, load plugin's textdomain, to retrieve correct translations
     */
    private function init_i18n_support() {
    	add_filter( 'locale', array( &$this, 'change_plugin_locale' ) ); // allow changing the plugin language
        $language_directory = basename( dirname( TABLEPRESS__FILE__ ) ) . '/i18n';
        load_plugin_textdomain( 'tablepress', false, $language_directory );
        remove_filter( 'locale', array( &$this, 'change_plugin_locale' ) );
    }

	/**
	 * Init list of actions that have a view with their titles/names/caps
	 */
	private function init_view_actions() {
		$this->view_actions = array(
			'list' => array(
				'show_entry' => true,
				'page_title' => __( 'All Tables &lsaquo; %s', 'tablepress' ),
				'admin_menu_title' => __( 'All Tables', 'tablepress' ),
				'nav_tab_title' => __( 'All Tables', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'add' => array(
				'show_entry' => true,
				'page_title' => __( 'Add new Table &lsaquo; %s', 'tablepress' ),
				'admin_menu_title' => __( 'Add new Table', 'tablepress' ),
				'nav_tab_title' => __( 'Add New', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'edit' => array(
				'show_entry' => false,
				'page_title' => '',
				'admin_menu_title' => '',
				'nav_tab_title' => '',
				'min_access_cap' => 'read'
			),
			'import' => array(
				'show_entry' => true,
				'page_title' => __( 'Import a Table &lsaquo; %s', 'tablepress' ),
				'admin_menu_title' => __( 'Import a Table', 'tablepress' ),
				'nav_tab_title' => __( 'Import', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'export' => array(
				'show_entry' => true,
				'page_title' => __( 'Export a Table &lsaquo; %s', 'tablepress' ),
				'admin_menu_title' => __( 'Export a Table', 'tablepress' ),
				'nav_tab_title' => __( 'Export', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'options' => array(
				'show_entry' => true,
				'page_title' => __( 'Plugin Options &lsaquo; %s', 'tablepress' ),
				'admin_menu_title' => __( 'Plugin Options', 'tablepress' ),
				'nav_tab_title' => __( 'Plugin Options', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'about' => array(
				'show_entry' => true,
				'page_title' => __( 'About TablePress &lsaquo; %s', 'tablepress' ),
				'admin_menu_title' => __( 'About TablePress', 'tablepress' ),
				'nav_tab_title' => __( 'About', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'debug' => array(
				'show_entry' => false,
				'page_title' => '',
				'admin_menu_title' => '',
				'nav_tab_title' => '',//__( 'Debug', 'tablepress' ),
				'min_access_cap' => 'read'
			)
		);
	}

    /**
     * Change the WordPress locale to the desired plugin locale, applied as a filter in get_locale(), while loading the plugin textdomain
	 *
	 * @param string $locale Current WordPress locale
	 * @return string TablePress locale
	 */
    public function change_plugin_locale( $locale ) {
    	$new_locale = $this->model_options->get( 'plugin_language' );
		$locale = ( ! empty( $new_locale ) && 'auto' != $new_locale ) ? $new_locale : $locale;
		return $locale;
    }

	/**
	 * HTTP POST actions
	 */

	/**
	 * List of Tables (button press), no real functionality, just temporary
	 */
/*	public function handle_post_action_list() {
		TablePress::check_nonce( 'list' );

		//process here your on $_POST validation and / or option saving
		$this->model_options->update( array( 'message_123' => true, 'message_456' => true ) );

		TablePress::redirect( array( 'action' => 'list', 'message' => 'success_show_messages' ) );
	}
*/
	/**
	 * Save a table from the "Edit" screen
	 */
/*	public function handle_post_action_edit() {
		$orig_table_id = ( !empty( $_POST['orig_table_id'] ) && absint( $_POST['orig_table_id'] )) ? absint( $_POST['orig_table_id'] ) : false;
		TablePress::check_nonce( 'edit', $orig_table_id );

		$table = ( !empty( $_POST['table'] ) && is_array( $_POST['table'] )) ? stripslashes_deep( $_POST['table'] ) : false;

		if ( false === $table || false === $orig_table_id )
			TablePress::redirect( array( 'action' => 'list', 'message' => 'error_save' ) );

		$table['id'] = $orig_table_id;
		$table['data'] = array( array( 'a' ) );
		$table['options'] = array( array( 'test_edit' ) );

		$table_id = $this->model_table->save( $table );
		if ( false === $table_id )
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table_id, 'message' => 'error_save' ) );

		TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table_id, 'message' => 'success_save' ) );
	}
*/
	/**
	 * Add a table, according to the parameters on the "Add a Table" screen
	 */
/*	public function handle_post_action_add() {
		TablePress::check_nonce( 'add' );

		if ( empty( $_POST['table'] ) || ! is_array( $_POST['table'] ) )
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add' ) );
		else
			$table = stripslashes_deep( $_POST['table'] );

		$table['data'] = array( array( 'a' ) );
		$table['options'] = array( array( 'test_add' ) );

		$table_id = $this->model_table->add( $table );
		if ( false === $table_id  )
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add' ) );

		TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table_id, 'message' => 'success_add' ) );
	}
*/
	/**
	 * Save changed "Plugin Options"
	 */
	public function handle_post_action_options() {
		TablePress::check_nonce( 'options' );

		if ( empty( $_POST['options'] ) || ! is_array( $_POST['options'] ) )
			TablePress::redirect( array( 'action' => 'options', 'message' => 'error_save' ) );
		else
			$posted_options = stripslashes_deep( $_POST['options'] );

		$new_options = array();

		if ( ! empty( $posted_options['admin_menu_parent_page'] ) && '-' != $posted_options['admin_menu_parent_page'] ) {
		 	$new_options['admin_menu_parent_page'] = $posted_options['admin_menu_parent_page'];
			// re-init parent information, as TablePress::redirect() URL might be wrong otherwise
			$this->parent_page = apply_filters( 'tablepress_admin_menu_parent_page', $posted_options['admin_menu_parent_page'] );
			$this->is_top_level_page = in_array( $this->parent_page, array( 'top', 'middle', 'bottom' ) );
		}

		if ( ! empty( $posted_options['plugin_language'] ) && '-' != $posted_options['plugin_language'] ) {
			// maybe add check in array available languages
			$new_options['plugin_language'] = $posted_options['plugin_language'];
		}

		// save gathered new options
		if ( ! empty( $new_options ) )
			$this->model_options->update( $new_options );

		TablePress::redirect( array( 'action' => 'options', 'message' => 'success_save' ) );
	}

	/**
	 * Save changes on the "Debug" screen
	 */
	public function handle_post_action_debug() {
		TablePress::check_nonce( 'debug' );

		if ( empty( $_POST['debug'] ) || ! is_array( $_POST['debug'] ) )
			TablePress::redirect( array( 'action' => 'debug', 'message' => 'error_save' ) );
		else
			$debug = stripslashes_deep( $_POST['debug'] );

		/*
		$new_tables = array();
		$new_tables['last_id'] = (int)$debug['last_id'];
		$new_tables['table_post'] = array();
		$count = count( $debug['table_post']['new_table_id'] );
		for( $i = 0; $i < $count; $i++ ) {
			if ( '' == $debug['table_post']['new_table_id'][ $i ] || '' == $debug['table_post']['new_post_id'][ $i ] )
				continue;
			$new_tables['table_post'][ (int)$debug['table_post']['new_table_id'][ $i ] ] = (int)$debug['table_post']['new_post_id'][ $i ];
		}
		$this->model_table->_debug_store_tables( $new_tables );
		*/

		// complete new options string, set directly with WordPress API
		update_option( 'tablepress_plugin_options', $debug['plugin_options'] );
		update_user_option( get_current_user_id(), 'tablepress_user_options', $debug['user_options'], false );

		TablePress::redirect( array( 'action' => 'debug', 'message' => 'success_save' ) );
	}

	/**
	 * Save GET actions
	 */

	/**
	 * Hide a header message on an admin screen
	 */
	public function handle_get_action_hide_message() {
		$message_item = ! empty( $_GET['item'] ) ? $_GET['item'] : '';
		TablePress::check_nonce( 'hide_message', $message_item );

		$this->model_options->update( "message_{$message_item}", false );

		$return = ! empty( $_GET['return'] ) ? $_GET['return'] : 'list';
		TablePress::redirect( array( 'action' => $return ) );
	}

	/**
	 * Delete a table
	 */	
/*	public function handle_get_action_delete_table() {
		$table_id = ( ! empty( $_GET['item'] ) && absint( $_GET['item'] ) ) ? absint( $_GET['item'] ) : false;
		TablePress::check_nonce( 'delete_table', $table_id );

		// caps check with correct user caps
		//if ( ! current_user_can( 'manage_options' ) )
		//	wp_die( __('Cheatin&#8217; uh?') );

		$return = ! empty( $_GET['return'] ) ? $_GET['return'] : 'list';
		$return_item = ! empty( $_GET['return_item'] ) ? (int)$_GET['return_item'] : false;

		if ( false === $table_id )
			TablePress::redirect( array( 'action' => $return, 'table_id' => $return_item, 'message' => 'error_delete' ) );

		$deleted = $this->model_table->delete( $table_id );
		if ( false === $deleted )
			TablePress::redirect( array( 'action' => $return, 'table_id' => $return_item, 'message' => 'error_delete' ) );

		TablePress::redirect( array( 'action' => 'list', 'message' => 'success_delete' ) );
	}
*/
} // class TablePress_Admin_Controller