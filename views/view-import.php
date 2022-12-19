<?php
/**
 * Import Table View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Import Table View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Import_View extends TablePress_View {

	/**
	 * List of WP feature pointers for this view.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $wp_pointers = array( 'tp20_import_drag_drop_detect_format' );

	/**
	 * Set up the view with data and do things that are specific for this view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view.
	 * @param array  $data   Data for this view.
	 */
	public function setup( $action, array $data ) {
		parent::setup( $action, $data );

		$this->admin_page->enqueue_style( 'jsuites' );
		$this->admin_page->enqueue_style( 'import', array( 'tablepress-jsuites' ) );
		$this->admin_page->enqueue_script( 'jsuites' );
		$this->admin_page->enqueue_script( 'import', array( 'tablepress-jsuites' ) );

		$this->process_action_messages( array(
			'error_import' => __( 'Error: The import failed.', 'tablepress' ),
		) );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		$this->add_meta_box( 'import-form', __( 'Import Tables', 'tablepress' ), array( $this, 'postbox_import_form' ), 'normal' );
	}

	/**
	 * Print the screen head text.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the text box.
	 */
	public function textbox_head( array $data, array $box ) {
		?>
		<p>
			<?php _e( 'TablePress can import tables from common spreadsheet applications, like XLSX files fom Excel, or CSV, ODS, HTML, and JSON files.', 'tablepress' ); ?>
		</p>
		<p>
			<?php _e( 'To import tables, select and enter the import source in the following form.', 'tablepress' ); ?>
			<?php _e( 'You can also choose to import it as a new table, to replace an existing table, or to append the rows to an existing table.', 'tablepress' ); ?>
		</p>
		<?php
	}

	/**
	 * Print the content of the "Import Tables" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_import_form( array $data, array $box ) {
		?>
<table class="tablepress-postbox-table fixed">
	<tr id="row-import-source">
		<th class="column-1" scope="row" id="import-source-header"><?php _e( 'Import Source', 'tablepress' ); ?>:</th>
		<td class="column-2">
			<label for="tables-import-source-file-upload"><input name="import[source]" id="tables-import-source-file-upload" type="radio" aria-labelledby="import-source-header" value="file-upload"<?php checked( $data['import_source'], 'file-upload', true ); ?> /> <?php _e( 'File Upload', 'tablepress' ); ?></label>
			<label for="tables-import-source-url"><input name="import[source]" id="tables-import-source-url" type="radio" aria-labelledby="import-source-header" value="url"<?php checked( $data['import_source'], 'url', true ); ?> /> <?php _e( 'URL', 'tablepress' ); ?></label>
			<?php if ( ( ! is_multisite() && current_user_can( 'manage_options' ) ) || is_super_admin() ) { ?>
			<label for="tables-import-source-server"><input name="import[source]" id="tables-import-source-server" type="radio" aria-labelledby="import-source-header" value="server"<?php checked( $data['import_source'], 'server', true ); ?> /> <?php _e( 'File on server', 'tablepress' ); ?></label>
			<?php } ?>
			<label for="tables-import-source-form-field"><input name="import[source]" id="tables-import-source-form-field" type="radio" aria-labelledby="import-source-header" value="form-field"<?php checked( $data['import_source'], 'form-field', true ); ?> /> <?php _e( 'Manual Input', 'tablepress' ); ?></label>
		</td>
	</tr>
	<tr id="row-import-source-file-upload" class="top-border bottom-border">
		<th class="column-1 top-align" scope="row"><label for="tables-import-file-upload"><?php _e( 'Select files', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<div class="file-upload-area">
				<input name="import_file_upload[]" id="tables-import-file-upload" type="file" multiple />
				<div id="tables-import-file-upload-dropzone" class="dropzone hide-if-no-js">
					<span><?php _e( 'Click to select files, or drag them here.', 'tablepress' ); ?></span>
				</div>
			</div>
			<?php
			if ( $data['zip_support_available'] ) {
				echo '<span class="description">' . __( 'You can also import multiple tables by placing them in a ZIP file.', 'tablepress' ) . '</span>';
			}
			?>
		</td>
	</tr>
	<tr id="row-import-source-url" class="top-border bottom-border">
		<th class="column-1 top-align" scope="row"><label for="tables-import-url"><?php _e( 'File URL', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<input type="text" name="import[url]" id="tables-import-url" class="large-text code" value="<?php echo esc_url( $data['import_url'] ); ?>" />
			<?php
			if ( $data['zip_support_available'] ) {
				echo '<br /><span class="description">' . __( 'You can also import multiple tables by placing them in a ZIP file.', 'tablepress' ) . '</span>';
			}
			?>
		</td>
	</tr>
		<?php if ( ( ! is_multisite() && current_user_can( 'manage_options' ) ) || is_super_admin() ) { ?>
	<tr id="row-import-source-server" class="top-border bottom-border">
		<th class="column-1 top-align" scope="row"><label for="tables-import-server"><?php _e( 'Server Path to file', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<input type="text" name="import[server]" id="tables-import-server" class="large-text code" value="<?php echo esc_attr( $data['import_server'] ); ?>" />
			<?php
			if ( $data['zip_support_available'] ) {
				echo '<br /><span class="description">' . __( 'You can also import multiple tables by placing them in a ZIP file.', 'tablepress' ) . '</span>';
			}
			?>
		</td>
	</tr>
	<?php } ?>
	<tr id="row-import-source-form-field" class="top-border bottom-border">
		<th class="column-1 top-align" scope="row"><label for="tables-import-form-field"><?php _e( 'Import data', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<textarea name="import[form-field]" id="tables-import-form-field" rows="15" cols="40" class="large-text code"><?php echo esc_textarea( $data['import_form-field'] ); ?></textarea>
		</td>
	</tr>
	<tr id="row-import-type" class="top-border">
		<th class="column-1" scope="row" id="import-type-header"><?php _e( 'Add, Replace, or Append?', 'tablepress' ); ?>:</th>
		<td class="column-2">
			<label for="tables-import-type-add"><input name="import[type]" id="tables-import-type-add" type="radio" aria-labelledby="import-type-header" value="add"<?php checked( $data['import_type'], 'add', true ); ?> /> <?php _e( 'Add as new table', 'tablepress' ); ?></label>
			<label for="tables-import-type-replace"><input name="import[type]" id="tables-import-type-replace" type="radio" aria-labelledby="import-type-header" value="replace"<?php checked( $data['import_type'], 'replace', true ); ?><?php disabled( $data['tables_count'] > 0, false, true ); ?> /> <?php _e( 'Replace existing table', 'tablepress' ); ?></label>
			<label for="tables-import-type-append"><input name="import[type]" id="tables-import-type-append" type="radio" aria-labelledby="import-type-header" value="append"<?php checked( $data['import_type'], 'append', true ); ?><?php disabled( $data['tables_count'] > 0, false, true ); ?> /> <?php _e( 'Append rows to existing table', 'tablepress' ); ?></label>
		</td>
	</tr>
	<tr id="row-import-existing-table" class="top-border bottom-border">
		<th class="column-1" scope="row"><label for="tables-import-existing-table"><?php _e( 'Table to replace or append to', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<select id="tables-import-existing-table" name="import[existing_table]"<?php disabled( $data['tables_count'] > 0, false, true ); ?>>
				<option value=""><?php _e( '— Select or type —', 'tablepress' ); ?></option>
			<?php
			foreach ( $data['table_ids'] as $table_id ) {
				$table = TablePress::$model_table->load( $table_id, false, false ); // Load table, without table data, options, and visibility settings.
				if ( ! current_user_can( 'tablepress_edit_table', $table['id'] ) ) {
					continue;
				}
				if ( '' === trim( $table['name'] ) ) {
					$table['name'] = __( '(no name)', 'tablepress' );
				}
				$text = esc_html( sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), $table['id'], $table['name'] ) );
				$selected = selected( $table['id'], $data['import_existing_table'], false );
				echo "<option{$selected} value=\"{$table['id']}\">{$text}</option>";
			}
			?>
			</select>
		</td>
	</tr>
	<tr class="top-border">
		<td class="column-1"></td>
		<td class="column-2"><input type="hidden" name="import[legacy_import]" value="<?php echo ( 'true' === $data['legacy_import'] ) ? 'true' : 'false'; ?>" /><input type="submit" value="<?php echo esc_attr_x( 'Import', 'button', 'tablepress' ); ?>" class="button button-primary button-large" id="import-submit-button" /></td>
	</tr>
</table>
		<?php
	}

	/**
	 * Sets the content for the WP feature pointer about the drag and drop import and format detection on the "Import" screen.
	 *
	 * @since 2.0.0
	 */
	public function wp_pointer_tp20_import_drag_drop_detect_format() {
		$content  = '<h3>' . __( 'TablePress feature: Drag and Drop Import with Format Detection', 'tablepress' ) . '</h3>';
		$content .= '<p>' . __( 'Did you know?', 'tablepress' ) . ' ' . __( 'The import of tables is now even more powerful! You can simply drag and drop your files into this area and TablePress will automatically detect the file format!', 'tablepress' ) . '</p>';

		$this->admin_page->print_wp_pointer_js(
			'tp20_import_drag_drop_detect_format',
			'#tables-import-file-upload-dropzone span',
			array(
				'content'  => $content,
				'position' => array( 'edge' => 'bottom', 'align' => 'center' ),
			)
		);
	}

} // class TablePress_Import_View
