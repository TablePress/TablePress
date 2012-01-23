<?php
/**
 * Admin AJAX Controller for TablePress with functionality for the AJAX backend
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin AJAX Controller class, extends Base Controller Class
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Admin_AJAX_Controller extends TablePress_Controller {

/*	protected $table = array();
	protected $known_ranges = array();
*/
	/**
	 * Initiate Admin AJAX functionality
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$ajax_actions = array( 'hide_message', 'save_table', 'preview_table' );
		foreach ( $ajax_actions as $action ) {
			add_action( "wp_ajax_tablepress_{$action}", array( &$this, "ajax_action_{$action}" ) );
		}
	}

	/**
	 * Hide a header message on an admin screen
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_hide_message() {
		if ( empty( $_GET['item'] ) )
			die( '0' );
		else
			$message_item = $_GET['item'];

		TablePress::check_nonce( 'hide_message', $message_item, '_wpnonce', true );

		// @TODO Capability check!

		$this->model_options->update( "message_{$message_item}", false );
		die( '1' );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_save_table() {
		if ( empty( $_POST['tablepress'] ) || empty( $_POST['tablepress']['orig_id'] ) )
			die( '-1' );

		$edit_table = stripslashes_deep( $_POST['tablepress'] );

		// check to see if the submitted nonce matches with the generated nonce we created earlier, dies -1 on fail
		TablePress::check_nonce( 'edit', $edit_table['orig_id'], '_ajax_nonce', true );

		// ignore the request if the current user doesn't have sufficient permissions
		// @TODO Capability check!

		// we will go without isset() checks for now, as these variables are set in JS, and not as form elements
		$edit_table['rows'] = intval( $edit_table['rows'] );
		$edit_table['columns'] = intval( $edit_table['columns'] );
		$edit_table['visibility'] = json_decode( $edit_table['visibility'], true );
		$edit_table['options'] = json_decode( $edit_table['options'], true );
		$edit_table['data'] = json_decode( $edit_table['data'], true );

		// consistency checks
		$success = true;

		// Table ID can't be empty and must not contain illegal characters
		if ( empty( $edit_table['id'] )
		|| $edit_table['id'] != preg_replace( '/[^a-zA-Z0-9_-]/', '', $edit_table['id'] ) )
			$success = false;

		if ( ! isset( $edit_table['name'] )
		|| ! isset( $edit_table['description'] ) )
			$success = false;

		// Number of rows and columns
		if ( 0 === $edit_table['rows'] || 0 === $edit_table['columns']
		|| $edit_table['rows'] !== count( $edit_table['data'] )
		|| $edit_table['columns'] !== count( $edit_table['data'][0] ) )
			$success = false;

		// Number of rows and columns for visibility arrays
		if ( $edit_table['rows'] !== count( $edit_table['visibility']['rows'] )
		|| $edit_table['columns'] !== count( $edit_table['visibility']['columns'] ) )
			$success = false;

		// count hidden and visible rows
		$num_visible_rows = count( array_keys( $edit_table['visibility']['rows'], 1 ) );
		$num_hidden_rows = count( array_keys( $edit_table['visibility']['rows'], 0 ) );
		// Check number of hidden and visible rows
		if ( $edit_table['visibility']['hidden_rows'] !== $num_hidden_rows
		|| ( $edit_table['rows'] - $edit_table['visibility']['hidden_rows'] ) !== $num_visible_rows )
			$success = false;

		// count hidden and visible columns
		$num_visible_columns = count( array_keys( $edit_table['visibility']['columns'], 1 ) );
		$num_hidden_columns = count( array_keys( $edit_table['visibility']['columns'], 0 ) );
		// Check number of hidden and visible columns
		if ( $edit_table['visibility']['hidden_columns'] !== $num_hidden_columns
		|| ( $edit_table['columns'] - $edit_table['visibility']['hidden_columns'] ) !== $num_visible_columns )
			$success = false;

		// generate the response
		$response = array();

		if ( $success ) {

			// original table
			$table = $this->model_table->load( $edit_table['orig_id'] );

			// replace original values with new ones
			$table['name'] = $edit_table['name'];
			$table['description'] = $edit_table['description'];
			$table['data'] = $edit_table['data'];
			$table['options'] = array(
				'last_action' => 'ajax_edit',
				'last_modified' => current_time( 'timestamp' ),
				'last_editor' => get_current_user_id(),
			);
			// check options that have a checkbox
			$checkbox_options = array( 'table_head', 'table_foot', 'alternating_row_colors', 'row_hover' );
			foreach ( $checkbox_options as $option ) {
				$table['options'][$option] = ( isset( $edit_table['options'][$option] ) && 'true' == $edit_table['options'][$option] );
			}
			// check options that have a selectbox
			$selectbox_options = array( 'print_name', 'print_description' );
			foreach ( $selectbox_options as $option ) {
				if ( isset( $edit_table['options'][$option] ) )
					$table['options'][$option] = $edit_table['options'][$option];
			}
			if ( isset( $edit_table['options']['extra_css_classes'] ) )
				$table['options']['extra_css_classes'] = preg_replace( '/[^a-zA-Z0-9_ -]/', '', $edit_table['options']['extra_css_classes'] );
			$table['visibility']['rows'] = $edit_table['visibility']['rows'];
			$table['visibility']['columns'] = $edit_table['visibility']['columns'];

			$saved = $this->model_table->save( $table );
			if ( false === $saved ) {
				$success = false;
				$message = 'error_save';
			} else {
				$message = 'success_save';
				if ( $table['id'] !== $edit_table['id'] ) { // if no table ID change necessary, we are done
					$id_changed = $this->model_table->change_table_id( $table['id'], $edit_table['id'] );
					if ( $id_changed ) {
						$table['id'] = $edit_table['id'];
						$message = 'success_save_success_id_change';
					} else {
						$message = 'success_save_error_id_change';
					}
				}

				$response['table_id'] = $table['id'];
				$response['new_edit_nonce'] = wp_create_nonce( TablePress::nonce( 'edit', $table['id'] ) );
				$response['new_preview_nonce'] = wp_create_nonce( TablePress::nonce( 'preview_table', $table['id'] ) );
				$response['last_modified'] = TablePress::format_datetime( $table['options']['last_modified'] );
				$response['last_editor'] = TablePress::get_last_editor( $table['options']['last_editor'] );
			}

			$response['success'] = $success;
			$response['message'] = $message;
		} else {
			$response['success'] = false;
			$response['message'] = 'error_save';
		}

		// response output
		header( 'Content-Type: application/json; charset=UTF-8' );
		echo json_encode( $response );

		exit;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_preview_table() {
		if ( empty( $_POST['tablepress'] ) || empty( $_POST['tablepress']['orig_id'] ) )
			die( '-1' );

		$preview_table = stripslashes_deep( $_POST['tablepress'] );

		// check to see if the submitted nonce matches with the generated nonce we created earlier, dies -1 on fail
		TablePress::check_nonce( 'preview_table', $preview_table['orig_id'], '_ajax_nonce', true );

		// ignore the request if the current user doesn't have sufficient permissions
		// @TODO Capability check!

		$preview_table['rows'] = intval( $preview_table['rows'] );
		$preview_table['columns'] = intval( $preview_table['columns'] );
		$preview_table['visibility'] = json_decode( $preview_table['visibility'], true );
		$preview_table['options'] = json_decode( $preview_table['options'], true );
		$preview_table['data'] = json_decode( $preview_table['data'], true );
		// @TODO: make consistency checks here?

        $default_atts = array(
            'id' => 0,
            'column_widths' => array(),
            'alternating_row_colors' => -1,
            'row_hover' => -1,
            'table_head' => -1,
            'first_column_th' => false,
            'table_foot' => -1,
            'print_name' => -1,
            'print_description' => -1,
            'cache_table_output' => -1,
            'extra_css_classes' => '', //@TODO: sanitize this parameter, if set
            'use_datatables' => -1,
            'datatables_sort' => -1,
            'datatables_paginate' => -1,
            'datatables_paginate_entries' => -1,
            'datatables_lengthchange' => -1,
            'datatables_filter' => -1,
            'datatables_info' => -1,
            'datatables_tabletools' => -1,
            'datatables_customcommands' => -1,
            'row_offset' => 1, // ATTENTION: MIGHT BE DROPPED IN FUTURE VERSIONS!
            'row_count' => null, // ATTENTION: MIGHT BE DROPPED IN FUTURE VERSIONS!
            'show_rows' => array(),
            'show_columns' => array(),
            'hide_rows' => array(),
            'hide_columns' => array(),
            'cellspacing' => false,
            'cellpadding' => false,
            'border' => false,
            'html_id' => 'test'
        );
		$render_options = shortcode_atts( $default_atts, $preview_table['options'] );

		// create a render class instance
		$_render = TablePress::load_class( 'TablePress_Render', 'class-render.php', 'classes' );
		$_render->set_input( $preview_table, $render_options );
		$head_html = $_render->get_preview_css();
		$body_html = $_render->get_output();

		$success = true;
		if ( false === $body_html ) { // doesn't happen yet
			$success = false;
			$body_html = '';
		}

		// generate the response
		$response = array(
			'success' => $success,
			'head_html' => $head_html,
			'body_html' => $body_html
		);

		// response output
		header( 'Content-Type: application/json; charset=UTF-8' );
		echo json_encode( $response );

		exit;
	}

} // class TablePress_Admin_AJAX_Controller