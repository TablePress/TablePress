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
		if ( empty( $_POST['tablepress'] ) || empty( $_POST['tablepress']['id'] ) )
			die( '-1' );
		else
			$edit_table = stripslashes_deep( $_POST['tablepress'] );

		// check to see if the submitted nonce matches with the generated nonce we created earlier, dies -1 on fail
		TablePress::check_nonce( 'edit', $edit_table['id'], '_ajax_nonce', true );

		// ignore the request if the current user doesn't have sufficient permissions
		// @TODO Capability check!

		// default response data:
		$success = false;
		$message = 'error_save';
		do { // to be able to "break;" (allows for better readable code)
			// Load existing table from DB
			$table = $this->model_table->load( $edit_table['id'] );
			if ( false === $table ) // maybe somehow load a new table here? ($this->model_table->get_table_template())?
				break;
			
			// Check and convert data that was transmitted as JSON
			if ( empty( $edit_table['data'] )
			|| empty( $edit_table['options'] )
			|| empty( $edit_table['visibility'] ) )
				break;
			$edit_table['data'] = json_decode( $edit_table['data'], true );
			$edit_table['options'] = json_decode( $edit_table['options'], true );
			$edit_table['visibility'] = json_decode( $edit_table['visibility'], true );

			// Check consistency of new table, and then merge with existing table
			$table = $this->model_table->prepare_table( $table, $edit_table, true, true );
			if ( false === $table )
				break;

			// Save updated table
			$saved = $this->model_table->save( $table );
			if ( false === $saved )
				break;

			// at this point, the table was saved successfully, possible ID change remains
			$success = true;
			$message = 'success_save';

			// Check if ID change is desired
			if ( $table['id'] === $table['new_id'] ) // if not, we are done
				break;

			// Change table ID
			$id_changed = $this->model_table->change_table_id( $table['id'], $table['new_id'] );
			if ( $id_changed ) {
				$message = 'success_save_success_id_change';
				$table['id'] = $table['new_id'];
			} else {
				$message = 'success_save_error_id_change';
			}
		} while ( false ); // do-while-loop through this exactly once, to be able to "break;" early

		// Generate the response
		$response = array( // common for all responses
			'success' => $success,
			'message' => $message
		);
		if ( $success ) {
			$response['table_id'] = $table['id'];
			$response['new_edit_nonce'] = wp_create_nonce( TablePress::nonce( 'edit', $table['id'] ) );
			$response['new_preview_nonce'] = wp_create_nonce( TablePress::nonce( 'preview_table', $table['id'] ) );
			$response['last_modified'] = TablePress::format_datetime( $table['last_modified'] );
			$response['last_editor'] = TablePress::get_last_editor( $table['options']['last_editor'] );
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
		if ( empty( $_POST['tablepress'] ) || empty( $_POST['tablepress']['id'] ) )
			die( '-1' );
		else
			$preview_table = stripslashes_deep( $_POST['tablepress'] );

		// check to see if the submitted nonce matches with the generated nonce we created earlier, dies -1 on fail
		TablePress::check_nonce( 'preview_table', $preview_table['id'], '_ajax_nonce', true );

		// ignore the request if the current user doesn't have sufficient permissions
		// @TODO Capability check!

		// default response data:
		$success = false;
		do { // to be able to "break;" (allows for better readable code)
			// Load existing table from DB
			$table = $this->model_table->load( $preview_table['id'] );
			if ( false === $table ) // maybe somehow load a new table here? ($this->model_table->get_table_template())?
				break;

			// Check and convert data that was transmitted as JSON
			if ( empty( $preview_table['data'] )
			|| empty( $preview_table['options'] )
			|| empty( $preview_table['visibility'] ) )
				break;
			$preview_table['data'] = json_decode( $preview_table['data'], true );
			$preview_table['options'] = json_decode( $preview_table['options'], true );
			$preview_table['visibility'] = json_decode( $preview_table['visibility'], true );

			// Check consistency of new table, and then merge with existing table
			$table = $this->model_table->prepare_table( $table, $preview_table, true, true );
			if ( false === $table )
				break;

			// If the ID has changed, and the new ID is valid, render with the new ID (important e.g. for CSS classes/HTML ID)
			if ( $table['id'] !== $table['new_id'] && 0 === preg_match( '/[^a-zA-Z0-9_-]/', $table['new_id'] ) )
				$table['id'] = $table['new_id'];

			// at this point, the table data is valid and can be rendered
			$success = true;
		} while ( false ); // do-while-loop through this exactly once, to be able to "break;" early

		if ( $success ) {
			// Create a render class instance
			$_render = TablePress::load_class( 'TablePress_Render', 'class-render.php', 'classes' );
			// Merge desired options with default render options (as not all of them are stored in the table options, but are just Shortcode parameters)
			$render_options = shortcode_atts( $_render->get_default_render_options(), $table['options'] );
			$_render->set_input( $table, $render_options );
			$head_html = $_render->get_preview_css();
			$body_html = $_render->get_output();
		} else {
			$head_html = '';
			$body_html = __( 'The preview could not be loaded.', 'tablepress' );
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