<?php
/**
 * Edit Table View
 *
 * @package TablePress
 * @subpackage Edit Table View
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Edit Table View class
 *
 * @since 1.0.0
 */
class TablePress_Edit_View extends TablePress_View {

	/**
	 * Set up the view with data and do things that are specific for this view
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		parent::setup( $action, $data );

		add_thickbox();
		$this->admin_page->enqueue_style( 'edit' );
		$this->admin_page->enqueue_script( 'edit', array( 'jquery', 'jquery-ui-sortable', 'json2' ), false );

		$this->action_messages = array(
			'success_save' => __( 'The table was saved successfully.', 'tablepress' ),
			'success_add' => __( 'The table was added successfully.', 'tablepress' ),
			'success_import' => __( 'The table was imported successfully.', 'tablepress' ),
			'error_save' => __( 'Error: The table could not be saved.', 'tablepress' ),
			'error_delete' => __( 'Error: The table could not be deleted.', 'tablepress' ),
		);
		if ( $data['message'] && isset( $this->action_messages[ $data['message'] ] ) ) {
			$class = ( in_array( $data['message'], array( 'error_save', 'error_delete' ) ) ) ? 'error' : 'updated' ;
			$this->add_header_message( "<strong>{$this->action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		$this->add_meta_box( 'table-information', __( 'Table Information', 'tablepress' ), array( &$this, 'postbox_table_information' ), 'normal' );
		$this->add_meta_box( 'table-content', __( 'Table Content', 'tablepress' ), array( &$this, 'postbox_content' ), 'normal' );
		$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'submit' );
	}

	/**
	 * Print hidden field with a nonce for the screen's action, to be transmitted in HTTP requests
	 *
	 * @since 1.0.0
	 * @uses wp_nonce_field()
	 *
	 * @param array $data Data for this screen
	 * @param array $box Information about the text box
	 */
	protected function action_nonce_field( $data, $box ) {
		// use custom nonce field here, that includes the table ID
		wp_nonce_field( TablePress::nonce( $this->action, $data['table']['id'] ) ); echo "\n";
		?>
		<input type="hidden" name="orig_table_id" value="<?php echo $data['table']['id']; ?>" />
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function postbox_table_information( $data, $box ) {
		?>
		<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="table_id"><?php esc_html_e( 'Table ID', 'tablepress' ); ?>:</label></th>
			<td><input type="text" name="table[id]" id="table_id" class="small-text" value="<?php echo esc_attr( $data['table']['id'] ); ?>" />
			<?php echo ' <a href="' . TablePress::url( array( 'action' => 'delete_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' ) . '">' . __( 'Delete', 'tablepress' ) . '</a>'; ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="table_name"><?php esc_html_e( 'Table Name', 'tablepress' ); ?>:</label></th>
			<td><input type="text" name="table[name]" id="table_name" class="large-text" value="<?php echo esc_attr( $data['table']['name'] ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="table_description"><?php esc_html_e( 'Description', 'tablepress' ); ?>:</label></th>
			<td><textarea name="table[description]" id="table_description" class="large-text" rows="5" cols="50"><?php echo esc_html( $data['table']['description'] ); ?></textarea></td>
		</tr>
		<?php if ( !empty( $data['table']['last_editor_id'] ) ) { ?>
		<tr valign="top">
			<th scope="row"><?php esc_html_e( 'Last Modified', 'tablepress' ); ?>:</th>
			<td><?php printf( __( '%1$s by %1$s', 'tablepress' ), esc_html( $data['table']['last_modified'] ), esc_html( $data['table']['last_editor_id'] ) ); ?></td>
		</tr>
		<?php } ?>
		</table>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function postbox_content( $data, $box ) {
		var_dump( $data['table']['data'] );
		var_dump( $data['table']['options'] );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function textbox_head( $data, $box ) {
		?>
		<p><?php _e( 'On this page, you can edit the content of the table.', 'tablepress' ); ?> <?php _e( 'It is also possible to change the table structure by inserting, deleting, moving, and swapping columns and rows.', 'tablepress' ); ?><br />
		<?php printf( __( 'To insert the table into a page, post or text-widget, copy the shortcode <strong>[table id=%s /]</strong> and paste it into the corresponding place in the editor.', 'tablepress' ), esc_html( $data['table']['id'] ) ); ?></p>
		<?php
	}

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the Edit Table screen';
	}

} // class TablePress_Edit_View