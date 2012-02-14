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

		$this->admin_page->enqueue_script( 'import', array( 'jquery' ) );

		$this->action_messages = array(
			'error_import' => __( 'Error: The import failed.', 'tablepress' ),
			'error_no_zip_import' => __( 'Error: Import of ZIP files is not available on this server.', 'tablepress' )
		);
		if ( $data['message'] && isset( $this->action_messages[ $data['message'] ] ) ) {
			$class = ( 'error' == substr( $data['message'], 0, 5 ) ) ? 'error' : 'updated';
			$this->add_header_message( "<strong>{$this->action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		$this->add_meta_box( 'import-form', __( 'Import Tables', 'tablepress' ), array( &$this, 'postbox_import_form' ), 'normal' );
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
		<p><?php _e( 'TablePress can import tables from existing data.', 'tablepress' ); ?> <?php _e( 'This may be a CSV, HTML, or JSON file, each with a certain structure.', 'tablepress' ); ?></p>
		<p><?php _e( 'To import an existing table, please select its format and the source for the import.', 'tablepress' ); ?> <?php if ( 0 < $data['tables_count'] ) _e( 'You can also decide, if you want to import it as a new table or replace an existing table.', 'tablepress' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_import_form( $data, $box ) {
?>
<table class="tablepress-postbox-table fixed">
<tbody>
	<tr id="row-import-source">
		<th class="column-1" scope="row"><?php _e( 'Import Source', 'tablepress' ); ?>:</th>
		<td class="column-2">
			<input name="import[source]" id="tables-import-source-file-upload" type="radio" value="file-upload"<?php checked( $data['import_source'], 'file-upload', true ); ?> /> <label for="tables-import-source-file-upload"><?php _e( 'File Upload', 'tablepress' ); ?></label>
			<input name="import[source]" id="tables-import-source-url" type="radio" value="url"<?php checked( $data['import_source'], 'url', true ); ?> /> <label for="tables-import-source-url"><?php _e( 'URL', 'tablepress' ); ?></label>
			<input name="import[source]" id="tables-import-source-server" type="radio" value="server"<?php checked( $data['import_source'], 'server', true ); ?> /> <label for="tables-import-source-server"><?php _e( 'File on server', 'tablepress' ); ?></label>
			<input name="import[source]" id="tables-import-source-form-field" type="radio" value="form-field"<?php checked( $data['import_source'], 'form-field', true ); ?> /> <label for="tables-import-source-form-field"><?php _e( 'Manual Input', 'tablepress' ); ?></label>
		</td>
	</tr>
	<tr id="row-import-source-file-upload" class="bottom-border">
		<th class="column-1 top-align" scope="row"><label for="tables-import-file-upload"><?php _e( 'Select file', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<input name="import_file_upload" id="tables-import-file-upload" type="file" class="large-text" style="box-sizing: border-box;" />
			<?php
				if ( $data['zip_support_available'] )
					echo '<br/><span class="description">' . __( 'You can import multiple tables by placing them in a ZIP file.', 'tablepress' ) . '</span>';
			?>
		</td>
	</tr>
	<tr id="row-import-source-url" class="bottom-border">
		<th class="column-1 top-align" scope="row"><label for="tables-import-url"><?php _e( 'File URL', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<input type="text" name="import[url]" id="tables-import-url" class="large-text" value="<?php echo $data['import_url']; ?>" />
			<?php
				if ( $data['zip_support_available'] )
					echo '<br/><span class="description">' . __( 'You can import multiple tables by placing them in a ZIP file.', 'tablepress' ) . '</span>';
			?>
		</td>
	</tr>
	<tr id="row-import-source-server" class="bottom-border">
		<th class="column-1 top-align" scope="row"><label for="tables-import-server"><?php _e( 'Server Path to file', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<input type="text" name="import[server]" id="tables-import-server" class="large-text" value="<?php echo $data['import_server']; ?>" />
			<?php
				if ( $data['zip_support_available'] )
					echo '<br/><span class="description">' . __( 'You can import multiple tables by placing them in a ZIP file.', 'tablepress' ) . '</span>';
			?>
		</td>
	</tr>
	<tr id="row-import-source-form-field" class="bottom-border">
		<th class="column-1 top-align" scope="row"><label for="tables-import-form-field"><?php _e( 'Import data', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<textarea name="import[form_field]" id="tables-import-form-field" rows="15" cols="40" class="large-text"><?php echo $data['import_form_field']; ?></textarea>
		</td>
	</tr>
	<tr class="top-border bottom-border">
		<th class="column-1" scope="row"><label for="tables-import-format"><?php _e( 'Import Format', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<select id="tables-import-format" name="import[format]">
			<?php
				foreach ( $data['import_formats'] as $format => $name ) {
					$selected = selected( $format, $data['import_format'], false );
					echo "<option{$selected} value=\"{$format}\">{$name}</option>";
				}
			?>
			</select>
			<?php
				if ( ! $data['html_import_support_available'] )
					echo '<br/><span class="description">' . __( 'Import of HTML files is not available on your server.', 'tablepress' ) . '</span>';
			?>
		</td>
	</tr>
	<tr id="row-import-add_replace" class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Add or Replace?', 'tablepress' ); ?>:</th>
		<td class="column-2">
			<input name="import[add_replace]" id="tables-import-add_replace-add" type="radio" value="add"<?php checked( $data['import_add_replace'], 'add', true ); ?> /> <label for="tables-import-add_replace-add"><?php _e( 'Add as new table', 'tablepress' ); ?></label>
			<input name="import[add_replace]" id="tables-import-add_replace-replace" type="radio" value="replace"<?php checked( $data['import_add_replace'], 'replace', true ); ?><?php disabled( $data['tables_count'] > 0, false, true ); ?> /> <label for="tables-import-add_replace-replace"><?php _e( 'Replace existing table', 'tablepress' ); ?></label>
		</td>
	</tr>
	<tr id="row-import-replace-table">
		<th class="column-1" scope="row"><label for="tables-import-replace-table"><?php _e( 'Table to replace', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<select id="tables-import-replace-table" name="import[replace_table]"<?php disabled( $data['tables_count'] > 0, false, true ); ?>>
				<option value=""><?php _e( 'Select:' ); ?></option>
			<?php
				foreach ( $data['tables'] as $table ) {
					if ( '' == trim( $table['name'] ) )
						$table['name'] = __( '(no name)', 'tablepress' );
					$text = esc_html( sprintf( __( 'ID %1$s: %2$s ', 'tablepress' ), $table['id'], $table['name'] ) );
					$selected = selected( $table['id'], $data['import_replace_table'], false );
					echo "<option{$selected} value=\"{$table['id']}\">{$text}</option>";
				}
			?>
			</select>
		</td>
	</tr>
</tbody>
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