<?php
/**
 * Import Table View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Import Table View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Import_View extends TablePress_View {

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

		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		$this->add_text_box( 'export-form', array( &$this, 'textbox_export_form' ), 'normal' );

		$this->data['submit_button_caption'] = __( 'Import Table', 'tablepress' );
		$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'submit' );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_head( $data, $box ) {
		?>
		<p><?php _e( 'TablePress can import tables from existing data.', 'tablepress' ); ?> <?php _e( 'This may be a CSV, XML or HTML file, each with a certain structure.', 'tablepress' ); ?></p>
		<p><?php _e( 'To import an existing table, please select its format and the source for the import.', 'tablepress' ); ?> <?php if ( 0 < $data['tables_count'] ) _e( 'You can also decide, if you want to import it as a new table or replace an existing table.', 'tablepress' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_export_form( $data, $box ) {
		?>
		<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="import_format"><?php _e( 'Select Import Format', 'tablepress' ); ?>:</label></th>
			<td><select id="import_format" name="import_format">
			<?php
				/*
				$import_formats = $this->import_instance->import_formats;
				foreach ( $import_formats as $import_format => $longname )
					echo "<option" . ( isset( $_POST['import_format'] ) && ( $import_format == $_POST['import_format'] ) ? ' selected="selected"': '' ) . " value=\"{$import_format}\">{$longname}</option>\n";
				*/
			?>
			</select></td>
		</tr>
		<?php if ( 0 < $data['tables_count'] ) : ?>
		<tr valign="top" class="tr-import-addreplace">
			<th scope="row"><?php _e( 'Add or Replace Table?', 'tablepress' ); ?>:</th>
			<td>
			<input name="import_addreplace" id="import_addreplace_add" type="radio" value="add" <?php //echo ( isset( $_POST['import_addreplace'] ) && 'add' != $_POST['import_addreplace'] ) ? '' : 'checked="checked" ' ; ?>/> <label for="import_addreplace_add"><?php _e( 'Add as new Table', 'tablepress' ); ?></label>
			<input name="import_addreplace" id="import_addreplace_replace" type="radio" value="replace" <?php //echo ( isset( $_POST['import_addreplace'] ) && 'replace' == $_POST['import_addreplace'] ) ? 'checked="checked" ': '' ; ?>/> <label for="import_addreplace_replace"><?php _e( 'Replace existing Table', 'tablepress' ); ?></label>
			</td>
		</tr>
		<tr valign="top" class="tr-import-addreplace-table">
			<th scope="row"><label for="import_addreplace_table"><?php _e( 'Select existing Table to Replace', 'tablepress' ); ?>:</label></th>
			<td><select id="import_addreplace_table" name="import_addreplace_table">
			<?php
				foreach ( $data['tables'] as $table ) {
					$id = esc_attr( $table['id'] );
					$name = esc_html( $table['name'] );
					$text = sprintf( __( '%1$s (ID %2$s)', 'tablepress' ), $name, $id );
					echo "<option value=\"{$id}\">{$text}</option>";
				}
			?>
			</select></td>
		</tr>
		<?php endif; ?>
		<tr valign="top" class="tr-import-from">
			<th scope="row"><?php _e( 'Select source for Import', 'tablepress' ); ?>:</th>
			<td>
			<input name="import_from" id="import_from_file" type="radio" value="file-upload" <?php //echo ( isset( $_POST['import_from'] ) && 'file-upload' != $_POST['import_from'] ) ? '' : 'checked="checked" ' ; ?>/> <label for="import_from_file"><?php _e( 'File upload', 'tablepress' ); ?></label>
			<input name="import_from" id="import_from_url" type="radio" value="url" <?php //echo ( isset( $_POST['import_from'] ) && 'url' == $_POST['import_from'] ) ? 'checked="checked" ': '' ; ?>/> <label for="import_from_url"><?php _e( 'URL', 'tablepress' ); ?></label>
			<input name="import_from" id="import_from_field" type="radio" value="form-field" <?php //echo ( isset( $_POST['import_from'] ) && 'form-field' == $_POST['import_from'] ) ? 'checked="checked" ': '' ; ?>/> <label for="import_from_field"><?php _e( 'Manual input', 'tablepress' ); ?></label>
			<input name="import_from" id="import_from_server" type="radio" value="server" <?php //echo ( isset( $_POST['import_from'] ) && 'server' == $_POST['import_from'] ) ? 'checked="checked" ': '' ; ?>/> <label for="import_from_server"><?php _e( 'File on server', 'tablepress' ); ?></label>
			</td>
		</tr>
		<tr valign="top" class="tr-import-file-upload">
			<th scope="row"><label for="import_file"><?php _e( 'Select File with Table to Import', 'tablepress' ); ?>:</label></th>
			<td><input name="import_file" id="import_file" type="file" class="regular-text" /></td>
		</tr>
		<tr valign="top" class="tr-import-url">
			<th scope="row"><label for="import_url"><?php _e( 'URL to Import Table from', 'tablepress' ); ?>:</label></th>
			<td><input type="text" name="import_url" id="import_url" class="regular-text" value="<?php //echo ( isset( $_POST['import_url'] ) ) ? $_POST['import_url'] : 'http://' ; ?>" /></td>
		</tr>
		<tr valign="top" class="tr-import-server">
			<th scope="row"><label for="import_server"><?php _e( 'Path to file on server', 'tablepress' ); ?>:</label></th>
			<td><input type="text" name="import_server" id="import_server" class="regular-text" value="<?php //echo ( isset( $_POST['import_server'] ) ) ? $_POST['import_server'] : '' ; ?>" /></td>
		</tr>
		<tr valign="top" class="tr-import-form-field">
			<th scope="row"><label for="import_data"><?php _e( 'Paste data with Table to Import', 'tablepress' ); ?>:</label></th>
			<td><textarea name="import_data" id="import_data" rows="15" cols="40" class="large-text"></textarea></td>
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
		return 'Help for the Import Table screen';
	}

} // class TablePress_Import_View