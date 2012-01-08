<?php
/**
 * Add Table View
 *
 * @package TablePress
 * @subpackage Add Table View
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Add Table View class
 *
 * @since 1.0.0
 */
class TablePress_Add_View extends TablePress_View {

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

		//$this->admin_page->enqueue_script( 'add', array( 'jquery' ) );

		$this->action_messages = array(
			'error_add' => __( 'Error: The table could not be added.', 'tablepress' ),
		);
		if ( $data['message'] && isset( $this->action_messages[ $data['message'] ] ) ) {
			$class = ( in_array( $data['message'], array( 'error_add' ) ) ) ? 'error' : 'updated' ;
			$this->add_header_message( "<strong>{$this->action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		$this->add_text_box( 'form-table', array( &$this, 'textbox_form_table' ), 'normal' );
		$this->data['submit_button_caption'] = __( 'Add Table', 'tablepress' );
		$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'submit' );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_head( $data, $box ) {
		?>
		<p><?php _e( 'To add a new table, enter its name, a description (optional) and the number of rows and columns.', 'tablepress' ); ?><br/><?php _e( 'You may also add, insert or delete rows and columns later.', 'tablepress' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_form_table( $data, $box ) {
		?>
		<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="table_name"><?php esc_html_e( 'Table Name', 'tablepress' ); ?>:</label></th>
			<td><input type="text" name="table[name]" id="table_name" class="large-text focus-blur-change" value="<?php esc_attr_e( 'Enter Table Name', 'tablepress' ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="table_description"><?php esc_html_e( 'Description', 'tablepress' ); ?>:</label></th>
			<td><textarea name="table[description]" id="table_description" class="large-text focus-blur-change" rows="5" cols="50"><?php esc_html_e( 'Enter Description', 'tablepress' ); ?></textarea></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="table_rows"><?php esc_html_e( 'Number of Rows', 'tablepress' ); ?>:</label></th>
			<td><input type="text" name="table[rows]" id="table_rows" class="small-text" value="5" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="table_cols"><?php esc_html_e( 'Number of Columns', 'tablepress' ); ?>:</label></th>
			<td><input type="text" name="table[cols]" id="table_cols" class="small-text" value="5" /></td>
		</tr>
		</table>
		<?php
	}

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the Add new Table screen';
	}

} // class TablePress_Add_View