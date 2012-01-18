<?php
/**
 * Admin Controller for TablePress with the functionality for the non-AJAX backend
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin Controller class, extends Base Controller Class
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Admin_Controller extends TablePress_Controller {

	/**
	 * Page hooks (i.e. names) WordPress uses for the TablePress admin screens,
	 * populated in add_admin_menu_entry()
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $page_hooks = array();

	/**
	 * Actions that have a view and admin menu or nav tab menu entry
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $view_actions = array();

	/**
	 * Initialize the Admin Controller, determine location the admin menu, set up actions
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'admin_menu', array( &$this, 'add_admin_menu_entry' ) );
		add_action( 'admin_init', array( &$this, 'add_admin_actions' ) );
	}

	/**
	 * Add admin screens to the correct place in the admin menu
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu_entry() {
		// for all menu entries:
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
			add_menu_page( 'TablePress', 'TablePress', $min_access_cap, 'tablepress', $callback, $icon_url, $position );
			foreach ( $this->view_actions as $action => $entry ) {
				if ( ! $entry['show_entry'] )
					continue;
				$slug = 'tablepress';
				if ( 'list' != $action )
					$slug .= '_' . $action;
				$this->page_hooks[] = add_submenu_page( 'tablepress', sprintf( __( '%s &lsaquo; TablePress', 'tablepress' ), $entry['page_title'] ) , $entry['admin_menu_title'], $entry['min_access_cap'], $slug, $callback );
			}
		} else {
			$this->page_hooks[] = add_submenu_page( $this->parent_page, 'TablePress', 'TablePress', $min_access_cap, 'tablepress', $callback );
		}
	}

	/**
	 * Set up handlers for user actions in the backend that exceed plain viewing
	 *
	 * @since 1.0.0
	 */
	public function add_admin_actions() {
		// register the callbacks for processing action requests
		$post_actions = array( 'add', 'edit', 'options' );
		$get_actions = array( 'hide_message', 'delete_table', 'copy_table' );
		foreach ( $post_actions as $action ) {
			add_action( "admin_post_tablepress_{$action}", array( &$this, "handle_post_action_{$action}" ) );
		}
		foreach ( $get_actions as $action ) {
			add_action( "admin_post_tablepress_{$action}", array( &$this, "handle_get_action_{$action}" ) );
		}

		// register callbacks to trigger load behavior for admin pages
		foreach ( $this->page_hooks as $page_hook ) {
			add_action( "load-{$page_hook}", array( &$this, 'load_admin_page' ) );
		}

		// not sure if this is needed:
		// add_action( 'load-plugins.php', array( &$this, 'plugin_notification' ) );
		// register_activation_hook( TABLEPRESS__FILE__, array( &$this, 'plugin_activation_hook' ) );
		// register_deactivation_hook( TABLEPRESS__FILE__, array( &$this, 'plugin_deactivation_hook' ) );
	}

	/**
	 * Prepare the rendering of an admin screen, by determining the current action, loading necessary data and initializing the view
	 *
	 * @since 1.0.0
	 */
	 public function load_admin_page() {
		// determine the action from either the GET parameter (for sub-menu entries, and the main admin menu entry)
		$action = ( ! empty( $_GET['action'] ) ) ? $_GET['action'] : 'list'; // default action is list
		if ( $this->is_top_level_page ) {
			// or for sub-menu entry of an admin menu "TablePress" entry, get it from the "page" GET parameter
			if ( 'tablepress' !== $_GET['page'] )
				// actions that are top-level entries, but don't have an action GET parameter (action is after last _ in string)
				$action = substr( $_GET['page'], 11 ); // $_GET['page'] has the format 'tablepress_{$action}'
		} else {
			// do this here in the else-part, instead of adding another if ( ! $this->is_top_level_page ) check
			$this->init_i18n_support(); // done here, as for sub menu admin pages this is the first time translated strings are needed
			$this->init_view_actions(); // for top-level menu entries, this has been done above, just like init_i18n_support()
		}

		// check if action is a supported action, and whether the user is allowed to access this screen
		if ( ! isset( $this->view_actions[ $action ] ) || ! current_user_can( $this->view_actions[ $action ]['min_access_cap'] ) )
			wp_die( __('You do not have sufficient permissions to access this page.') );

		// changes current screen ID and pagenow variable in JS, to enable automatic meta box JS handling
		set_current_screen( "tablepress_{$action}" );

		// pre-define some table data
		$data = array(
			'action' => $action,
			'view_actions' => $this->view_actions,
			'message' => ( ! empty( $_GET['message'] ) ) ? $_GET['message'] : false
		);

		// depending on action, load more necessary data for the corresponding view
		switch ( $action ) {
			case 'list':
				$data['tables'] = $this->model_table->load_all();
				$data['tables_count'] = $this->model_table->count_tables();
				$data['messages']['first_visit'] = $this->model_options->get( 'message_first_visit' );
				$data['messages']['plugin_update'] = $this->model_options->get( 'message_plugin_update' );
				break;
			case 'options':
				$data['user_options']['parent_page'] = $this->parent_page;
				$data['user_options']['plugin_language'] = $this->model_options->get( 'plugin_language' );
				$data['user_options']['available_plugin_languages'] = array(
					'en_US' => __( 'English', 'tablepress' ),
					'de_DE' => __( 'German', 'tablepress' )
				); // make this a function or property
				break;
			case 'edit':
				if ( ! empty( $_GET['table_id'] ) ) {
					$data['table'] = $this->model_table->load( $_GET['table_id'] );
					if ( false === $data['table'] )
						TablePress::redirect( array( 'action' => 'list', 'message' => 'error_load_table' ) );
				} else {
					TablePress::redirect( array( 'action' => 'list', 'message' => 'error_no_table' ) );
				}
				break;
		/*
			case 'export':
				$data['tables'] = $this->model_table->load_all();
				$data['tables_count'] = $this->model_table->count_tables();
				if ( ! empty( $_GET['table_id'] ) ) {
					$data['table_id'] = $_GET['table_id'];
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
		*/
		}

		$data = apply_filters( 'tablepress_view_data', $data );

		// prepare and initialize the view
		$this->view = TablePress::load_view( $action, $data );
	}

	/**
	 * Render the view that has been initialized in load_admin_page() (called by WordPress when the actual page content is needed)
	 *
	 * @since 1.0.0
	 */
	public function show_admin_page() {
		$this->view->render();
	}

	/**
	 * Initialize i18n support, load plugin's textdomain, to retrieve correct translations
	 *
	 * @since 1.0.0
	 */
	protected function init_i18n_support() {
		add_filter( 'locale', array( &$this, 'change_plugin_locale' ) ); // allow changing the plugin language
		$language_directory = basename( dirname( TABLEPRESS__FILE__ ) ) . '/i18n';
		load_plugin_textdomain( 'tablepress', false, $language_directory );
		remove_filter( 'locale', array( &$this, 'change_plugin_locale' ) );
	}

	/**
	 * Init list of actions that have a view with their titles/names/caps
	 *
	 * @since 1.0.0
	 */
	protected function init_view_actions() {
		$this->view_actions = array(
			'list' => array(
				'show_entry' => true,
				'page_title' => __( 'All Tables', 'tablepress' ),
				'admin_menu_title' => __( 'All Tables', 'tablepress' ),
				'nav_tab_title' => __( 'All Tables', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'add' => array(
				'show_entry' => true,
				'page_title' => __( 'Add New Table', 'tablepress' ),
				'admin_menu_title' => __( 'Add New Table', 'tablepress' ),
				'nav_tab_title' => __( 'Add New', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'edit' => array(
				'show_entry' => false,
				'page_title' => __( 'Edit Table', 'tablepress' ),
				'admin_menu_title' => '',
				'nav_tab_title' => '',
				'min_access_cap' => 'read'
			),
			'import' => array(
				'show_entry' => true,
				'page_title' => __( 'Import a Table', 'tablepress' ),
				'admin_menu_title' => __( 'Import a Table', 'tablepress' ),
				'nav_tab_title' => __( 'Import', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'export' => array(
				'show_entry' => true,
				'page_title' => __( 'Export a Table', 'tablepress' ),
				'admin_menu_title' => __( 'Export a Table', 'tablepress' ),
				'nav_tab_title' => __( 'Export', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'options' => array(
				'show_entry' => true,
				'page_title' => __( 'Plugin Options', 'tablepress' ),
				'admin_menu_title' => __( 'Plugin Options', 'tablepress' ),
				'nav_tab_title' => __( 'Plugin Options', 'tablepress' ),
				'min_access_cap' => 'read'
			),
			'about' => array(
				'show_entry' => true,
				'page_title' => __( 'About', 'tablepress' ),
				'admin_menu_title' => __( 'About TablePress', 'tablepress' ),
				'nav_tab_title' => __( 'About', 'tablepress' ),
				'min_access_cap' => 'read'
			)
		);

		$this->view_actions = apply_filters( 'tablepress_admin_view_actions', $this->view_actions );
	}

	/**
	 * Change the WordPress locale to the desired plugin locale, applied as a filter in get_locale(), while loading the plugin textdomain
	 *
	 * @since 1.0.0
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
	 *
	 * @TODO: STILL REQUIRED:
	 * caps check with correct user caps, like
	 * // if ( ! current_user_can( 'manage_options' ) )
	 * //	wp_die( __('Cheatin&#8217; uh?') );
	 */

	/**
	 * Save a table after the "Edit" screen was submitted
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_edit() {
		if ( empty( $_POST['table'] ) || empty( $_POST['table']['orig_id'] ) )
			TablePress::redirect( array( 'action' => 'list', 'message' => 'error_save' ) );

		$edit_table = stripslashes_deep( $_POST['table'] );

		TablePress::check_nonce( 'edit', $edit_table['orig_id'] );

		// consistency checks
		$success = true;
		if ( ! isset( $edit_table['name'] )
		|| ! isset( $edit_table['description'] )
		|| ! isset( $edit_table['number'] ) )
			$success = false;
		$edit_table['number']['rows'] = intval( $edit_table['number']['rows'] );
		$edit_table['number']['columns'] = intval( $edit_table['number']['columns'] );
		if ( ! isset( $edit_table['data'] )
		|| $edit_table['number']['rows'] !== count( $edit_table['data'] )
		|| $edit_table['number']['columns'] !== count( $edit_table['data'][0] ) )
			$success = false;
		if ( ! isset( $edit_table['visibility'] )
		|| $edit_table['number']['rows'] !== count( $edit_table['visibility']['rows'] )
		|| $edit_table['number']['columns'] !== count( $edit_table['visibility']['columns'] ) )
			$success = false;

		if ( false === $success )
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $edit_table['orig_id'], 'message' => 'error_save' ) );

		$table = $this->model_table->load( $edit_table['orig_id'] );
		// replace original values with new ones from form fields
		$table['name'] = $edit_table['name'];
		$table['description'] = $edit_table['description'];
		// Table Data
		$table['data'] = $edit_table['data'];
		// Table Options
		$updated_options = array(
			'last_action' => 'edit',
			'last_modified' => current_time( 'timestamp' ),
			'last_editor' => get_current_user_id()
		);
		$table['options'] = array_merge( $table['options'], $updated_options );
		$table['options']['table_head'] = ( isset( $edit_table['options']['table_head'] ) && 'true' == $edit_table['options']['table_head'] );
		$table['options']['table_foot'] = ( isset( $edit_table['options']['table_foot'] ) && 'true' == $edit_table['options']['table_foot'] );
		// Table Visibility
		$table['visibility']['rows'] = array_map( 'intval', $edit_table['visibility']['rows'] );
		$table['visibility']['columns'] = array_map( 'intval', $edit_table['visibility']['columns'] );

		// Save updated Table
		$saved = $this->model_table->save( $table );
		if ( false === $saved )
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table['id'], 'message' => 'error_save' ) );

		if ( $table['id'] === $edit_table['id'] ) // if no table ID change necessary, we are done
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table['id'], 'message' => 'success_save' ) );

		$id_changed = $this->model_table->change_table_id( $table['id'], $edit_table['id'] );
		if ( $id_changed )
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $edit_table['id'], 'message' => 'success_save_success_id_change' ) );
		else
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table['id'], 'message' => 'success_save_error_id_change' ) );
	}

	/**
	 * Add a table, according to the parameters on the "Add new Table" screen
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_add() {
		TablePress::check_nonce( 'add' );

		if ( empty( $_POST['table'] ) || ! is_array( $_POST['table'] ) )
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add' ) );
		else
			$add_table = stripslashes_deep( $_POST['table'] );

		// sanity checks
		$name = ( isset( $add_table['name'] ) ) ? $add_table['name'] : '';
		$description = ( isset( $add_table['description'] ) ) ? $add_table['description'] : '';
		$num_rows = ( isset( $add_table['rows'] ) ) ? absint( $add_table['rows'] ) : 1;
		if ( $num_rows < 1 )
			$num_rows = 1;
		$num_columns = ( isset( $add_table['columns'] ) ) ? absint( $add_table['columns'] ) : 1;
		if ( $num_columns < 1 )
			$num_columns = 1;

		// create a new table array with default data
		$table = array();
		$table['name'] = $name;
		$table['description'] = $description;
		$table['data'] = array_fill( 0, $num_rows, array_fill( 0, $num_columns, '' ) );
		$table['options'] = array(
			'last_action' => 'add',
			'last_modified' => current_time( 'timestamp' ),
			'last_editor' => get_current_user_id(),
			'table_head' => true,
			'table_foot' => true
		);
		$table['visibility'] = array(
			'rows' => array_fill( 0, $num_rows, 1 ),
			'columns' => array_fill( 0, $num_columns, 1 )
		);

		$table_id = $this->model_table->add( $table );
		if ( false === $table_id )
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add' ) );

		TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table_id, 'message' => 'success_add' ) );
	}

	/**
	 * Save changed "Plugin Options"
	 *
	 * @since 1.0.0
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
	 * Save GET actions
	 *
	 * @TODO: STILL REQUIRED:
	 * caps check with correct user caps, like
	 * // if ( ! current_user_can( 'manage_options' ) )
	 * //	wp_die( __('Cheatin&#8217; uh?') );
	 */

	/**
	 * Hide a header message on an admin screen
	 *
	 * @since 1.0.0
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
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_delete_table() {
		$table_id = ( ! empty( $_GET['item'] ) ) ? $_GET['item'] : false;
		TablePress::check_nonce( 'delete_table', $table_id );

		$return = ! empty( $_GET['return'] ) ? $_GET['return'] : 'list';
		$return_item = ! empty( $_GET['return_item'] ) ? $_GET['return_item'] : false;

		if ( false === $table_id ) // nonce check should actually catch this already
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_delete', 'table_id' => $return_item ) );

		$deleted = $this->model_table->delete( $table_id );
		if ( false === $deleted )
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_delete', 'table_id' => $return_item ) );

		TablePress::redirect( array( 'action' => 'list', 'message' => 'success_delete', 'table_id' => $return_item ) );
	}

	/**
	 * Copy a table
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_copy_table() {
		$table_id = ( ! empty( $_GET['item'] ) ) ? $_GET['item'] : false;
		TablePress::check_nonce( 'copy_table', $table_id );

		$return = ! empty( $_GET['return'] ) ? $_GET['return'] : 'list';
		$return_item = ! empty( $_GET['return_item'] ) ? $_GET['return_item'] : false;

		if ( false === $table_id ) // nonce check should actually catch this already
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_copy', 'table_id' => $return_item ) );

		// load table to copy
		$table = $this->model_table->load( $table_id );

		// adjust name and options of copied table
		$table['name'] = sprintf( __( 'Copy of %s', 'tablepress' ), $table['name'] );
		$updated_options = array(
			'last_action' => 'copy',
			'last_modified' => current_time( 'timestamp' ),
			'last_editor' => get_current_user_id(),
		);
		$table['options'] = array_merge( $table['options'], $updated_options );

		$table_id = $this->model_table->add( $table );
		if ( false === $table_id )
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_copy', 'table_id' => $return_item ) );

		TablePress::redirect( array( 'action' => 'list', 'message' => 'success_copy', 'table_id' => $return_item ) );
	}

} // class TablePress_Admin_Controller