<?php
/**
 * Export Table View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Export Table View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Export_View extends TablePress_View {

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

		$this->process_action_messages( array(
			'error_export'          => __( 'Error: The export failed.', 'tablepress' ),
			'error_load_table'      => __( 'Error: This table could not be loaded!', 'tablepress' ),
			'error_table_corrupted' => __( 'Error: The internal data of this table is corrupted!', 'tablepress' ),
			'error_create_zip_file' => __( 'Error: The ZIP file could not be created.', 'tablepress' ),
		) );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		if ( 0 === $data['tables_count'] ) {
			$this->add_meta_box( 'no-tables', __( 'Export Tables', 'tablepress' ), array( $this, 'postbox_no_tables' ), 'normal' );
		} else {
			$this->admin_page->enqueue_script( 'export' );
			$this->add_meta_box( 'export-form', __( 'Export Tables', 'tablepress' ), array( $this, 'postbox_export_form' ), 'normal' );
		}
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
			<?php _e( 'Exporting a table allows you to use it in other programs, like spreadsheets applications.', 'tablepress' ); ?>
			<?php _e( 'Regularly exporting tables is also recommended as a backup of your data.', 'tablepress' ); ?>
		</p>
		<p>
			<?php _e( 'To export, select the tables and the desired export format.', 'tablepress' ); ?>
			<?php _e( 'If you choose more than one table, the exported files will automatically be stored in a ZIP archive file.', 'tablepress' ); ?>
		<br />
			<?php _e( 'Be aware that for the CSV and HTML formats only the table data, but no table options are exported!', 'tablepress' ); ?>
			<?php _e( 'For the JSON format, the table data and the table options are exported.', 'tablepress' ); ?>
		</p>
		<?php
	}

	/**
	 * Print the content of the "No tables found" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_no_tables( array $data, array $box ) {
		$add_url = TablePress::url( array( 'action' => 'add' ) );
		$import_url = TablePress::url( array( 'action' => 'import' ) );
		?>
		<p><?php _e( 'No tables found.', 'tablepress' ); ?></p>
		<p><?php printf( __( 'You should <a href="%1$s">add</a> or <a href="%2$s">import</a> a table to get started!', 'tablepress' ), $add_url, $import_url ); ?></p>
		<?php
	}

	/**
	 * Print the content of the "Export Tables" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_export_form( array $data, array $box ) {
		?>
<table class="tablepress-postbox-table fixed">
	<tr>
		<th class="column-1 top-align" scope="row">
			<label for="tables-export"><?php _e( 'Tables to Export', 'tablepress' ); ?>:</label>
			<?php
			if ( $data['zip_support_available'] ) {
				// Show a "Select all" checkbox to select all entries in the export tables dropdown.
				echo '<br /><br /><label for="tables-export-select-all"><input type="checkbox" id="tables-export-select-all" /> ' . __( 'Select all', 'tablepress' ) . '</label>';

				// Show a "Reverse List" checkbox if more tables are shown than what the height of the export tables dropdown holds.
				if ( $data['tables_count'] > 12 ) {
					echo '<br /><br /><label for="tables-export-reverse-list"><input type="checkbox" id="tables-export-reverse-list" /> ' . __( 'Reverse list', 'tablepress' ) . '</label>';
				}
			}
			?>
		</th>
		<td class="column-2">
			<input type="hidden" name="export[tables_list]" id="tables-export-list" value="" />
			<?php
				$select_size = $data['tables_count'] + 1; // Show at least one empty row in the select.
				$select_size = max( $select_size, 3 );
				$select_size = min( $select_size, 12 );
				$size_multiple = ( $data['zip_support_available'] ) ? " size=\"{$select_size}\" multiple=\"multiple\"" : '';
			?>
			<select id="tables-export" name="export[tables][]" autofocus<?php echo $size_multiple; ?>>
			<?php
			foreach ( $data['table_ids'] as $table_id ) {
				// Load table, without table data, options, and visibility settings.
				$table = TablePress::$model_table->load( $table_id, false, false );
				if ( ! current_user_can( 'tablepress_export_table', $table['id'] ) ) {
					continue;
				}
				if ( '' === trim( $table['name'] ) ) {
					$table['name'] = __( '(no name)', 'tablepress' );
				}
				$text = esc_html( sprintf( __( 'ID %1$s: %2$s', 'tablepress' ), $table['id'], $table['name'] ) );
				$selected = selected( true, in_array( $table['id'], $data['export_ids'], true ), false );
				echo "<option{$selected} value=\"{$table['id']}\">{$text}</option>";
			}
			?>
			</select>
			<?php
			if ( $data['zip_support_available'] ) {
				// The keyboard shortcuts in this description are for Windows keyboard layouts. The Mac keys (⌘ and shift) are set via JS in export.js.
				echo '<br /><span id="tables-export-shortcut-description" class="description">' . sprintf( __( 'You can select multiple tables by holding down the “%1$s” key or the “%2$s” key for ranges.', 'tablepress' ), _x( 'Ctrl', 'keyboard key', 'tablepress' ), _x( 'Shift', 'keyboard key', 'tablepress' ) ) . '</span>';
			}
			?>
		</td>
	</tr>
	<tr>
		<th class="column-1" scope="row"><label for="tables-export-format"><?php _e( 'Export Format', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<select id="tables-export-format" name="export[format]">
			<?php
			foreach ( $data['export_formats'] as $format => $name ) {
				$selected = selected( $format, $data['export_format'], false );
				echo "<option{$selected} value=\"{$format}\">{$name}</option>";
			}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<th class="column-1" scope="row"><label for="tables-export-csv-delimiter"><?php _e( 'CSV Delimiter', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<select id="tables-export-csv-delimiter" name="export[csv_delimiter]">
			<?php
			foreach ( $data['csv_delimiters'] as $delimiter => $name ) {
				$selected = selected( $delimiter, $data['csv_delimiter'], false );
				echo "<option{$selected} value=\"{$delimiter}\">{$name}</option>";
			}
			?>
			</select> <span id="tables-export-csv-delimiter-description" class="description hide-if-js"><?php _e( '(Only needed for CSV export.)', 'tablepress' ); ?></span>
		</td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'ZIP file', 'tablepress' ); ?>:</th>
		<td class="column-2">
		<?php
		if ( $data['zip_support_available'] ) {
			?>
		<input type="checkbox" id="tables-export-zip-file" name="export[zip_file]" value="true" />
		<label for="tables-export-zip-file"><?php _e( 'Create a ZIP archive.', 'tablepress' ); ?> <span id="tables-export-zip-file-description" class="description hide-if-js"><?php _e( '(Mandatory if more than one table is selected.)', 'tablepress' ); ?></span></label>
			<?php
		} else {
			_e( 'Note: Support for ZIP file creation seems not to be available on this server.', 'tablepress' );
		}
		?>
		</td>
	</tr>
	<tr class="top-border">
		<td class="column-1"></td>
		<td class="column-2"><input type="submit" value="<?php echo esc_attr_x( 'Download Export File', 'button', 'tablepress' ); ?>" class="button button-primary button-large" /></td>
	</tr>
</table>
		<?php
	}

} // class TablePress_Export_View
