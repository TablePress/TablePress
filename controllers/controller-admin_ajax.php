<?php
/**
 * Admin AJAX Controller for TablePress with functionality for the AJAX backend
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin AJAX Controller class, extends Base Controller Class
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Admin_AJAX_Controller extends TablePress_Controller {

	/**
	 * Initiates the Admin AJAX functionality.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Buffer all outputs, to prevent errors/warnings being printed that make the JSON invalid.
		ob_start();

		parent::__construct();

		$ajax_actions = array( 'hide_message', 'save_table', 'preview_table', 'save_screen_options' );
		foreach ( $ajax_actions as $action ) {
			add_action( "wp_ajax_tablepress_{$action}", array( $this, "ajax_action_{$action}" ) );
		}
	}

	/**
	 * Hides a header message on an admin screen.
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_hide_message() {
		if ( empty( $_GET['item'] ) ) {
			wp_die( '0' );
		}

		$message_item = $_GET['item'];

		TablePress::check_nonce( 'hide_message', $message_item, '_wpnonce', true );

		if ( ! current_user_can( 'tablepress_list_tables' ) ) {
			wp_die( '-1' );
		}

		TablePress::$model_options->update( "message_{$message_item}", false );

		wp_die( '1' );
	}

	/**
	 * Saves the table after the "Save Changes" button on the "Edit" screen has been clicked.
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_save_table() {
		if ( empty( $_POST['tablepress']['id'] ) ) {
			wp_die( '-1' );
		}

		$edit_table = wp_unslash( $_POST['tablepress'] );

		// Check if the submitted nonce matches the generated nonce we created earlier, dies -1 on failure.
		TablePress::check_nonce( 'edit', $edit_table['id'], '_ajax_nonce', true );

		// Ignore the request if the current user doesn't have sufficient permissions.
		if ( ! current_user_can( 'tablepress_edit_table', $edit_table['id'] ) ) {
			wp_die( '-1' );
		}

		// Default response data.
		$success = false;
		$message = 'error_save';
		$error_details = '';
		do { // to be able to "break;" (allows for better readable code)
			// Load table, without table data, but with options and visibility settings.
			$existing_table = TablePress::$model_table->load( $edit_table['id'], false, true );
			if ( is_wp_error( $existing_table ) ) { // maybe somehow load a new table here? (TablePress::$model_table->get_table_template())?
				$error = new WP_Error( 'ajax_save_table_load', '', $edit_table['id'] );
				$error->merge_from( $existing_table );
				$error_details = TablePress::get_wp_error_string( $error );
				break;
			}

			// Check and convert data that was transmitted as JSON.
			if ( empty( $edit_table['data'] )
			|| empty( $edit_table['options'] )
			|| empty( $edit_table['visibility'] ) ) {
				$error = new WP_Error( 'ajax_save_table_data_empty', '', $edit_table['id'] );
				$error_details = TablePress::get_wp_error_string( $error );
				break;
			}
			$edit_table['data'] = (array) json_decode( $edit_table['data'], true );
			$edit_table['options'] = (array) json_decode( $edit_table['options'], true );
			$edit_table['visibility'] = (array) json_decode( $edit_table['visibility'], true );

			// Check consistency of new table, and then merge with existing table.
			$table = TablePress::$model_table->prepare_table( $existing_table, $edit_table, true );
			if ( is_wp_error( $table ) ) {
				$error = new WP_Error( 'ajax_save_table_prepare', '', $edit_table['id'] );
				$error->merge_from( $table );
				$error_details = TablePress::get_wp_error_string( $error );
				break;
			}

			// DataTables Custom Commands can only be edited by trusted users.
			if ( ! current_user_can( 'unfiltered_html' ) ) {
				$table['options']['datatables_custom_commands'] = $existing_table['options']['datatables_custom_commands'];
			}

			// Save updated table.
			$saved = TablePress::$model_table->save( $table );
			if ( is_wp_error( $saved ) ) {
				$error = new WP_Error( 'ajax_save_table_save', '', $table['id'] );
				$error->merge_from( $saved );
				$error_details = TablePress::get_wp_error_string( $error );
				break;
			}

			// At this point, the table was saved successfully, possible ID change remains.
			$success = true;
			$message = 'success_save';

			// Check if ID change is desired.
			if ( $table['id'] === $table['new_id'] ) {
				// If not, we are done.
				break;
			}

			// Change table ID.
			if ( current_user_can( 'tablepress_edit_table_id', $table['id'] ) ) {
				$id_changed = TablePress::$model_table->change_table_id( $table['id'], $table['new_id'] );
				if ( ! is_wp_error( $id_changed ) ) {
					$message = 'success_save_success_id_change';
					$table['id'] = $table['new_id'];
				} else {
					$message = 'success_save_error_id_change';
					$error = new WP_Error( 'ajax_save_table_id_change', '', $table['new_id'] );
					$error->merge_from( $id_changed );
					$error_details = TablePress::get_wp_error_string( $error );
				}
			} else {
				$message = 'success_save_error_id_change';
				$error_details = 'table_id_could_not_be_changed: capability_check_failed';
			}
		} while ( false ); // Do-while-loop through this exactly once, to be able to "break;" early.

		// Generate the response.

		// Common data for all responses.
		$response = array(
			'success' => $success,
			'message' => $message,
		);
		if ( $success ) {
			$response['table_id'] = $table['id'];
			$response['new_edit_nonce'] = wp_create_nonce( TablePress::nonce( 'edit', $table['id'] ) );
			$response['new_preview_nonce'] = wp_create_nonce( TablePress::nonce( 'preview_table', $table['id'] ) );
			$response['last_modified'] = TablePress::format_datetime( $table['last_modified'] );
			$response['last_editor'] = TablePress::get_user_display_name( $table['options']['last_editor'] );
		}
		if ( ! empty( $error_details ) ) {
			$response['error_details'] = esc_html( $error_details );
		}
		// Buffer all outputs, to prevent errors/warnings being printed that make the JSON invalid.
		$output_buffer = ob_get_clean();
		if ( ! empty( $output_buffer ) ) {
			$response['output_buffer'] = $output_buffer;
		}

		// Send the response.
		wp_send_json( $response );
	}

	/**
	 * Returns the live preview data of table that has non-saved changes.
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_preview_table() {
		if ( empty( $_POST['tablepress']['id'] ) ) {
			wp_die( '-1' );
		}

		$preview_table = wp_unslash( $_POST['tablepress'] );

		// Check if the submitted nonce matches the generated nonce we created earlier, dies -1 on failure.
		TablePress::check_nonce( 'preview_table', $preview_table['id'], '_ajax_nonce', true );

		// Ignore the request if the current user doesn't have sufficient permissions.
		if ( ! current_user_can( 'tablepress_preview_table', $preview_table['id'] ) ) {
			wp_die( '-1' );
		}

		// Default response data.
		$success = false;
		do { // to be able to "break;" (allows for better readable code)
			// Load table, without table data, but with options and visibility settings.
			$existing_table = TablePress::$model_table->load( $preview_table['id'], false, true );
			if ( is_wp_error( $existing_table ) ) { // maybe somehow load a new table here? (TablePress::$model_table->get_table_template())?
				break;
			}

			// Check and convert data that was transmitted as JSON.
			if ( empty( $preview_table['data'] )
			|| empty( $preview_table['options'] )
			|| empty( $preview_table['visibility'] ) ) {
				break;
			}
			$preview_table['data'] = (array) json_decode( $preview_table['data'], true );
			$preview_table['options'] = (array) json_decode( $preview_table['options'], true );
			$preview_table['visibility'] = (array) json_decode( $preview_table['visibility'], true );

			// Check consistency of new table, and then merge with existing table.
			$table = TablePress::$model_table->prepare_table( $existing_table, $preview_table, true );
			if ( is_wp_error( $table ) ) {
				break;
			}

			// DataTables Custom Commands can only be edited by trusted users.
			if ( ! current_user_can( 'unfiltered_html' ) ) {
				$table['options']['datatables_custom_commands'] = $existing_table['options']['datatables_custom_commands'];
			}

			// If the ID has changed, and the new ID is valid, render with the new ID (important e.g. for CSS classes/HTML ID).
			if ( $table['id'] !== $table['new_id'] && 0 === preg_match( '/[^a-zA-Z0-9_-]/', $table['new_id'] ) ) {
				$table['id'] = $table['new_id'];
			}

			// Sanitize all table data to remove unsafe HTML from the preview output, if the user is not allowed to work with unfiltered HTML.
			if ( ! current_user_can( 'unfiltered_html' ) ) {
				$table = TablePress::$model_table->sanitize( $table );
			}

			// At this point, the table data is valid and sanitized and can be rendered.
			$success = true;
		} while ( false ); // Do-while-loop through this exactly once, to be able to "break;" early.

		if ( $success ) {
			// Create a render class instance.
			$_render = TablePress::load_class( 'TablePress_Render', 'class-render.php', 'classes' );
			// Merge desired options with default render options (see TablePress_Controller_Frontend::shortcode_table()).
			$default_render_options = $_render->get_default_render_options();
			/** This filter is documented in controllers/controller-frontend.php */
			$default_render_options = apply_filters( 'tablepress_shortcode_table_default_shortcode_atts', $default_render_options );
			$render_options = shortcode_atts( $default_render_options, $table['options'] );
			/** This filter is documented in controllers/controller-frontend.php */
			$render_options = apply_filters( 'tablepress_shortcode_table_shortcode_atts', $render_options );
			$render_options['html_id'] = "tablepress-{$table['id']}";
			$_render->set_input( $table, $render_options );
			$head_html = $_render->get_preview_css();
			$custom_css = TablePress::$model_options->get( 'custom_css' );
			$use_custom_css = ( TablePress::$model_options->get( 'use_custom_css' ) && '' !== $custom_css );
			if ( $use_custom_css ) {
				$head_html .= "<style>\n{$custom_css}\n</style>\n";
			}

			$body_html = '<div id="tablepress-page"><p>'
				. __( 'This is a preview of your table.', 'tablepress' ) . ' '
				. __( 'Because of CSS styling in your theme, the table might look different on your page!', 'tablepress' ) . ' '
				. __( 'The Table Features for Site Visitors, like sorting, filtering, and pagination, are also not available in this preview!', 'tablepress' ) . '<br />';
			// Show the instructions string depending on whether the Block Editor is used on the site or not.
			if ( TablePress::site_uses_block_editor() ) {
				$body_html .= sprintf( __( 'To insert a table into a post or page, add a “%1$s” block in the block editor and select the desired table.', 'tablepress' ), __( 'TablePress table', 'tablepress' ) );
			} else {
				$body_html .= __( 'To insert a table into a post or page, paste its Shortcode at the desired place in the editor.', 'tablepress' ) . ' '
					. __( 'Each table has a unique ID that needs to be adjusted in that Shortcode.', 'tablepress' );
			}
			$body_html .= '</p>' . $_render->get_output() . '</div>';
		} else {
			$head_html = '';
			$body_html = __( 'The preview could not be loaded.', 'tablepress' );
		}

		// Generate the response.
		$response = array(
			'success'   => $success,
			'head_html' => $head_html,
			'body_html' => $body_html,
		);
		// Buffer all outputs, to prevent errors/warnings being printed that make the JSON invalid.
		$output_buffer = ob_get_clean();
		if ( ! empty( $output_buffer ) ) {
			$response['output_buffer'] = $output_buffer;
		}

		// Send the response.
		wp_send_json( $response );
	}

	/**
	 * Saves the screen options on the "Edit" screen when they are changed.
	 *
	 * @since 2.1.0
	 */
	public function ajax_action_save_screen_options() {
		// Check if the submitted nonce matches the generated nonce we created earlier, dies -1 on failure.
		TablePress::check_nonce( 'screen_options', false, '_ajax_nonce', true );

		if ( empty( $_POST['tablepress'] ) ) {
			wp_die( '-1' );
		}
		$screen_options = wp_unslash( $_POST['tablepress'] );

		// Sanitize and limit values to a minimum and a maximum.
		$new_screen_options = array();

		if ( isset( $screen_options['table_editor_column_width'] ) ) {
			$new_screen_options['table_editor_column_width'] = absint( $screen_options['table_editor_column_width'] );
			$new_screen_options['table_editor_column_width'] = max( $new_screen_options['table_editor_column_width'], 30 ); // Minimum width: 30 pixels.
			$new_screen_options['table_editor_column_width'] = min( $new_screen_options['table_editor_column_width'], 9999 ); // Maximum width: 9999 pixels.
		}

		if ( isset( $screen_options['table_editor_line_clamp'] ) ) {
			$new_screen_options['table_editor_line_clamp'] = absint( $screen_options['table_editor_line_clamp'] );
			$new_screen_options['table_editor_line_clamp'] = min( $new_screen_options['table_editor_line_clamp'], 999 ); // Maximum lines: 999. Minimum of 0 (for all lines) is ensured by absint().
		}

		if ( empty( $new_screen_options ) ) {
			wp_die( '-1' );
		}
		TablePress::$model_options->update( $new_screen_options );

		// Generate the response.
		$response = array(
			'success' => true,
		);

		// Buffer all outputs, to prevent errors/warnings being printed that make the JSON invalid.
		$output_buffer = ob_get_clean();
		if ( ! empty( $output_buffer ) ) {
			$response['output_buffer'] = $output_buffer;
		}

		// Send the response.
		wp_send_json( $response );
	}

} // class TablePress_Admin_AJAX_Controller
