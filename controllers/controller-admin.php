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
	 * Boolean to record whether language support has been loaded (to prevent to do it twice)
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	protected $i18n_support_loaded = false;

	/**
	 * Initialize the Admin Controller, determine location the admin menu, set up actions
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		// handler for changing the number of shown tables in the list of tables (via WP List Table class)
		add_filter( 'set-screen-option', array( &$this, 'save_list_tables_screen_option' ), 10, 3 );

		add_action( 'admin_menu', array( &$this, 'add_admin_menu_entry' ) );
		add_action( 'admin_init', array( &$this, 'add_admin_actions' ) );
	}

	/**
	 * Handler for changing the number of shown tables in the list of tables (via WP List Table class)
	 *
	 * @since 1.0.0
	 *
	 * @param bool $false Current value of the filter (probably bool false)
	 * @param string $option Option in which the setting is stored
	 * @param int $value Current value of the setting
	 * @return bool|int False to not save the changed setting, or the int value to be saved
	 */
	public function save_list_tables_screen_option( $false, $option, $value ) {
		if ( 'tablepress_list_per_page' == $option )
			return $value;
		else
			return $false;
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
			$this->init_i18n_support(); // done here as translated strings for admin menu are needed already
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
		$post_actions = array( 'list', 'add', 'edit', 'options' );
		$get_actions = array( 'hide_message', 'delete_table', 'copy_table', 'preview_table', 'editor_button_thickbox' );
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

		$pages_with_editor_button = array( 'post.php', 'post-new.php' );
		foreach ( $pages_with_editor_button as $editor_page ) {
			add_action( "load-{$editor_page}", array( &$this, 'add_editor_buttons' ) );
		}

		if ( ! is_network_admin() && ! is_user_admin() )
			add_action( 'admin_bar_menu', array( &$this, 'add_wp_admin_bar_new_content_menu_entry' ), 71 );

		// not sure if this is needed:
		// add_action( 'load-plugins.php', array( &$this, 'plugin_notification' ) );
	}

	/**
	 * Register actions to add "Table" button to "HTML editor" and "Visual editor" toolbars
	 *
	 * @since 1.0.0
	 */
	public function add_editor_buttons() {
		$this->init_i18n_support();
		add_thickbox(); // usually already loaded by media upload functions
		$admin_page = TablePress::load_class( 'TablePress_Admin_Page', 'class-admin-page-helper.php', 'classes' );
		$admin_page->enqueue_script( 'quicktags-button', array( 'quicktags', 'media-upload' ), array(
			'editor_button' => array(
				'caption' => __( 'Table', 'tablepress' ),
				'title' => __( 'Insert a Table from TablePress', 'tablepress' ),
				'thickbox_title' => __( 'Insert a Table from TablePress', 'tablepress' ),
				'thickbox_url' => TablePress::url( array( 'action' => 'editor_button_thickbox' ), true, 'admin-post.php' )
			)
		) );

		// TinyMCE integration
		if ( user_can_richedit() ) {
			add_filter( 'mce_external_plugins', array( &$this, 'add_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( &$this, 'add_tinymce_button' ) );
		}
	}

	/**
	 * Add "Table" button and separator to the TinyMCE toolbar
	 *
	 * @param array $buttons Current set of buttons in the TinyMCE toolbar
	 * @return array Current set of buttons in the TinyMCE toolbar, including "Table" button
	 */
	public function add_tinymce_button( $buttons ) {
		$buttons[] = '|';
		$buttons[] = 'tablepress_insert_table';
		return $buttons;
	}

	/**
	 * Register "Table" button plugin to TinyMCE
	 *
	 * @param array $plugins Current set of registered TinyMCE plugins
	 * @return array Current set of registered TinyMCE plugins, including "Table" button plugin
	 */
	public function add_tinymce_plugin( $plugins ) {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.dev' : '';
		$js_file = "admin/tinymce-button{$suffix}.js";
		$plugins['tablepress_tinymce'] = plugins_url( $js_file, TABLEPRESS__FILE__ );
		return $plugins;
	}

	/**
	 * Add "TablePress Table" entry to "New" dropdown menu in the WP Admin Bar
	 *
	 * @since 1.0.0
	 *
	 * @param object $wp_admin_bar The current WP Admin Bar object
	 */
	public function add_wp_admin_bar_new_content_menu_entry( $wp_admin_bar ) {
		// @TODO: Translation might not work, as textdomain might not yet be loaded here (for submenu entries)
		// Might need $this->init_i18n_support(); here
		$wp_admin_bar->add_menu( array(
			'parent'    => 'new-content',
			'id'        => 'new-tablepress-table',
			'title'     => __( 'TablePress Table', 'tablepress' ),
			'href'      => TablePress::url( array( 'action' => 'add' ) )
		) );
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
			'view_actions' => $this->view_actions,
			'message' => ( ! empty( $_GET['message'] ) ) ? $_GET['message'] : false
		);

		// depending on action, load more necessary data for the corresponding view
		switch ( $action ) {
			case 'list':
				$data['tables'] = $this->model_table->load_all();
				$data['messages']['first_visit'] = $this->model_options->get( 'message_first_visit' );
				$data['messages']['plugin_update'] = $this->model_options->get( 'message_plugin_update' );
				break;
			case 'options':
				// Maybe try saving "Custom CSS" to a file:
				// (called here, as the credentials form posts to this handler again, due to how request_filesystem_credentials() works)
				if ( isset( $_GET['item'] ) && 'save_custom_css' == $_GET['item'] ) {
					TablePress::check_nonce( 'options', $_GET['item'] ); // nonce check here, as we don't have an explicit handler, and even viewing the screen needs to be checked
					$action = 'options_custom_css'; // to load a different view
					// try saving "Custom CSS" to a file, otherwise this gets the HTML for the credentials form
					$result = $this->model_options->save_custom_css_to_file();
					$data['credentials_form'] = $result;
					break;
				}
				$data['frontend_options']['use_custom_css_file'] = $this->model_options->get( 'use_custom_css_file' );
				$data['frontend_options']['custom_css'] = $this->model_options->load_custom_css_from_file();
				$data['frontend_options']['custom_css_file_exists'] = ( false !== $data['frontend_options']['custom_css'] );
				if ( $data['frontend_options']['use_custom_css_file'] ) {
					// fall back to "Custom CSS" in options, if it could not be retrieved from file
					if ( ! $data['frontend_options']['custom_css_file_exists'] )
						$data['frontend_options']['custom_css'] = $this->model_options->get( 'custom_css' );
				} else {
					// get "Custom CSS" from options
					$data['frontend_options']['custom_css'] = $this->model_options->get( 'custom_css' );
				}
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
			case 'export':
				$data['tables'] = $this->model_table->load_all();
				$data['tables_count'] = $this->model_table->count_tables();
				if ( ! empty( $_GET['table_id'] ) ) {
					$data['table_id'] = $_GET['table_id'];
					// this is actually done in the post_import handler function
					$data['table'] = $this->model_table->load( $data['table_id'] );
					if ( false === $data['table'] )
						TablePress::redirect( array( 'action' => 'list', 'message' => 'error_load_table' ) );
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

		$data = apply_filters( 'tablepress_view_data', $data, $action );

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
		if ( $this->i18n_support_loaded )
			return;
		add_filter( 'locale', array( &$this, 'change_plugin_locale' ) ); // allow changing the plugin language
		$language_directory = basename( dirname( TABLEPRESS__FILE__ ) ) . '/i18n';
		load_plugin_textdomain( 'tablepress', false, $language_directory );
		remove_filter( 'locale', array( &$this, 'change_plugin_locale' ) );
		$this->i18n_support_loaded = true;
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
	 * Handle Bulk Actions (Delete, Copy) on "All Tables" list screen
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_list() {
		TablePress::check_nonce( 'list' );

		if ( isset( $_POST['bulk-action-top'] ) && '-1' != $_POST['bulk-action-top'] )
			$bulk_action = $_POST['bulk-action-top'];
		elseif ( isset( $_POST['bulk-action-bottom'] ) && '-1' != $_POST['bulk-action-bottom'] )
			$bulk_action = $_POST['bulk-action-bottom'];
		else
			$bulk_action = false;

		if ( ! in_array( $bulk_action, array( 'copy', 'delete' ) ) )		
			TablePress::redirect( array( 'action' => 'list', 'message' => 'error_bulk_action_invalid' ) );

		// @TODO: caps check for selected bulk action

		if ( empty( $_POST['table'] ) || ! is_array( $_POST['table'] ) )
			TablePress::redirect( array( 'action' => 'list', 'message' => "error_no_selection" ) );
		else
			$tables = stripslashes_deep( $_POST['table'] );

		$no_success = array(); // to store table IDs that failed

		switch( $bulk_action ) {
			case 'copy':
				foreach ( $tables as $table_id ) {
					$copy_table_id = $this->model_table->copy( $table_id );
					if ( false === $copy_table_id )
						$no_success[] = $table_id;
				}
				break;
			case 'delete':
				foreach ( $tables as $table_id ) {
					$deleted = $this->model_table->delete( $table_id );
					if ( false === $deleted )
						$no_success[] = $table_id;
				}
				break;
		}

		if ( count( $no_success ) != 0 ) { // maybe pass this information to the view?
			$message = "error_{$bulk_action}_not_all_tables";
		} else {
			$plural = ( count( $tables ) > 1 ) ? '_plural' : '';
			$message = "success_{$bulk_action}{$plural}";
		}

		// slightly more complex redirect method, to account for sort, search, and pagination in the WP_List_Table on the List View
		// but only if this action succeeds, to have everything fresh in the event of an error
		$sendback = wp_get_referer();
		if ( ! $sendback ) {
			$sendback = TablePress::url( array( 'action' => 'list', 'message' => $message ) );
		} else {
			$sendback = remove_query_arg( array( 'action', 'message', 'table_id' ), $sendback );
			$sendback = add_query_arg( array( 'action' => 'list', 'message' => $message ), $sendback );
		}
		wp_redirect( $sendback );
		exit();
	}

	/**
	 * Save a table after the "Edit" screen was submitted
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_edit() {
		if ( empty( $_POST['table'] ) || empty( $_POST['table']['id'] ) )
			TablePress::redirect( array( 'action' => 'list', 'message' => 'error_save' ) );
		else
			$edit_table = stripslashes_deep( $_POST['table'] );

		TablePress::check_nonce( 'edit', $edit_table['id'], 'nonce-edit-table' );

		// Options array must exist, so that checkboxes can be evaluated
		if ( empty( $edit_table['options'] ) )
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $edit_table['id'], 'message' => 'error_save' ) );

		// Evaluate options that have a checkbox (only necessary in Admin Controller, where they might not be set (if unchecked))
		$checkbox_options = array(
			'table_head', 'table_foot', 'alternating_row_colors', 'row_hover',	// Table Options
			'use_datatables', 'datatables_sort', 'datatables_filter'			// DataTables JS Features @TODO: THIS NEEDS WORK (e.g. for disabled fields)!
		);
		foreach ( $checkbox_options as $option ) {
			$edit_table['options'][$option] = ( isset( $edit_table['options'][$option] ) && 'true' === $edit_table['options'][$option] );
		}

		// Load existing table from DB
		$table = $this->model_table->load( $edit_table['id'] );
		if ( false === $table ) // maybe somehow load a new table here? ($this->model_table->get_table_template())?
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $edit_table['id'], 'message' => 'error_save' ) );

		// Check consistency of new table, and then merge with existing table
		$table = $this->model_table->prepare_table( $table, $edit_table );
		if ( false === $table )
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $edit_table['id'], 'message' => 'error_save' ) );

		// Save updated table
		$saved = $this->model_table->save( $table );
		if ( false === $saved )
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table['id'], 'message' => 'error_save' ) );

		// Check if ID change is desired
		if ( $table['id'] === $table['new_id'] ) // if not, we are done
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table['id'], 'message' => 'success_save' ) );

		// Change table ID
		$id_changed = $this->model_table->change_table_id( $table['id'], $table['new_id'] );
		if ( $id_changed )
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table['new_id'], 'message' => 'success_save_success_id_change' ) );
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

		// Perform sanity checks of posted data
		// @TODO: maybe redirect to error instead of setting default values for name/description/numbers:
		$name = ( isset( $add_table['name'] ) ) ? $add_table['name'] : '';
		$description = ( isset( $add_table['description'] ) ) ? $add_table['description'] : '';
		$num_rows = ( isset( $add_table['rows'] ) ) ? absint( $add_table['rows'] ) : 1;
		if ( $num_rows < 1 )
			$num_rows = 1;
		$num_columns = ( isset( $add_table['columns'] ) ) ? absint( $add_table['columns'] ) : 1;
		if ( $num_columns < 1 )
			$num_columns = 1;

		// Create a new table array with information from the posted data
		$new_table = array(
			'name' => $name,
			'description' => $description,
			'data' => array_fill( 0, $num_rows, array_fill( 0, $num_columns, '' ) ),
			'visibility' => array(
				'rows' => array_fill( 0, $num_rows, 1 ),
				'columns' => array_fill( 0, $num_columns, 1 )
			)
		);
		// Merge this data into an empty table template
		$table = $this->model_table->prepare_table( $this->model_table->get_table_template(), $new_table, false );
		if ( false === $table )
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add' ) );

		// Add the new table (and get its first ID)
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

		// Valid new options that will be merged into existing ones
		$new_options = array();

		// Check each posted option value, and (maybe) add it to the new options
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
		// Checkbox
		$new_options['use_custom_css_file'] = ( isset( $posted_options['use_custom_css_file'] ) && 'true' === $posted_options['use_custom_css_file'] );
		if ( isset( $posted_options['custom_css'] ) ) {
			if ( 1 === preg_match( '#<style.*?>(.*?)</style>#is', $posted_options['custom_css'], $matches ) )
				$posted_options['custom_css'] = trim( $matches[1] ); // if found, take match as style to save
			// Save "Custom CSS" to option
			$new_options['custom_css'] = $posted_options['custom_css'];
			// Maybe save it to file as well
			$update_custom_css_file = false;
			if ( $new_options['use_custom_css_file']
			&& $new_options['custom_css'] !== $this->model_options->load_custom_css_from_file() ) { // only write to file, if CSS really changed
				$update_custom_css_file = true;
				// Set to false again. As it was set here, it will be set true again, if file saving succeeds
				$new_options['use_custom_css_file'] = false;
			}
		}

		// save gathered new options (will be merged into existing ones)
		if ( ! empty( $new_options ) )
			$this->model_options->update( $new_options );

		if ( $update_custom_css_file )
			TablePress::redirect( array( 'action' => 'options', 'item' => 'save_custom_css' ), true );

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

		// slightly more complex redirect method, to account for sort, search, and pagination in the WP_List_Table on the List View
		// but only if this action succeeds, to have everything fresh in the event of an error
		$sendback = wp_get_referer();
		if ( ! $sendback ) {
			$sendback = TablePress::url( array( 'action' => 'list', 'message' => 'success_delete', 'table_id' => $return_item ) );
		} else {
			$sendback = remove_query_arg( array( 'action', 'message', 'table_id' ), $sendback );
			$sendback = add_query_arg( array( 'action' => 'list', 'message' => 'success_delete', 'table_id' => $return_item ), $sendback );
		}
		wp_redirect( $sendback );
		exit();
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

		$copy_table_id = $this->model_table->copy( $table_id );
		if ( false === $copy_table_id )
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_copy', 'table_id' => $return_item ) );

		// slightly more complex redirect method, to account for sort, search, and pagination in the WP_List_Table on the List View
		// but only if this action succeeds, to have everything fresh in the event of an error
		$sendback = wp_get_referer();
		if ( ! $sendback ) {
			$sendback = TablePress::url( array( 'action' => 'list', 'message' => 'success_copy', 'table_id' => $return_item ) );
		} else {
			$sendback = remove_query_arg( array( 'action', 'message', 'table_id' ), $sendback );
			$sendback = add_query_arg( array( 'action' => 'list', 'message' => 'success_copy', 'table_id' => $return_item ), $sendback );
		}
		wp_redirect( $sendback );
		exit();
	}

	/**
	 * Preview a table
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_preview_table() {
		$table_id = ( ! empty( $_GET['item'] ) ) ? $_GET['item'] : false;
		TablePress::check_nonce( 'preview_table', $table_id );

		if ( false === $table_id ) // nonce check should actually catch this already
			wp_die( __( 'The preview could not be loaded.', 'tablepress' ), __( 'Preview', 'tablepress' ) );

		// Load existing table from DB
		$table = $this->model_table->load( $table_id );
		if ( false === $table )
			wp_die( __( 'The table could not be loaded.', 'tablepress' ), __( 'Preview', 'tablepress' ) );

		// Create a render class instance
		$_render = TablePress::load_class( 'TablePress_Render', 'class-render.php', 'classes' );
		// Merge desired options with default render options (as not all of them are stored in the table options, but are just Shortcode parameters)
		$render_options = shortcode_atts( $_render->get_default_render_options(), $table['options'] );
		$_render->set_input( $table, $render_options );
		$view_data = array(
			'head_html' => $_render->get_preview_css(),
			'body_html' => $_render->get_output()
		);

		if ( $this->model_options->get( 'use_custom_css_file' ) ) {
			$custom_css = $this->model_options->load_custom_css_from_file();
			// fall back to "Custom CSS" in options, if it could not be retrieved from file
			if ( false === $custom_css  )
				$custom_css = $this->model_options->get( 'custom_css' );
		} else {
			// get "Custom CSS" from options
			$custom_css = $this->model_options->get( 'custom_css' );
		}

		if ( ! empty( $custom_css ) )
			$view_data['head_html'] .= "<style type=\"text/css\">\n{$custom_css}\n</style>\n";

		// Prepare, initialize, and render the view
		$this->view = TablePress::load_view( 'preview_table', $view_data );
		$this->view->render();
	}

	/**
	 * Show a list of tables in the Editor toolbar Thickbox (opened by TinyMCE or Quicktags button)
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_editor_button_thickbox() {
		TablePress::check_nonce( 'editor_button_thickbox' );

		$view_data = array(
			'tables' => $this->model_table->load_all()
		);

		// Prepare, initialize, and render the view
		$this->view = TablePress::load_view( 'editor_button_thickbox', $view_data );
		$this->view->render();
	}

} // class TablePress_Admin_Controller