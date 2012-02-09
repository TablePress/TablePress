<?php
/**
 * Export Table View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Export Table View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Export_View extends TablePress_View {

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

		$this->action_messages = array(
			'error_export' => __( 'Error: The export failed.', 'tablepress' ),
			'error_load_table' => __( 'Error: This table could not be loaded!', 'tablepress' ),
			'error_create_zip_file' => __( 'Error: The ZIP file could not be created.', 'tablepress' )
		);
		if ( $data['message'] && isset( $this->action_messages[ $data['message'] ] ) ) {
			$class = ( 'error' == substr( $data['message'], 0, 5 ) ) ? 'error' : 'updated';
			$this->add_header_message( "<strong>{$this->action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		if ( 0 == $data['tables_count'] ) {
			$this->add_meta_box( 'no-tables', __( 'Export Tables', 'tablepress' ), array( &$this, 'postbox_no_tables' ), 'normal' );
		} else {
			$this->admin_page->enqueue_script( 'export', array( 'jquery' ) );
			$this->add_meta_box( 'export-form', __( 'Export Tables', 'tablepress' ), array( &$this, 'postbox_export_form' ), 'normal' );
			$this->data['submit_button_caption'] = __( 'Export Table', 'tablepress' );
			$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'submit' );
		}
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_head( $data, $box ) {
		?>
		<p>
			<?php _e( 'It is recommended to export and backup the data of important tables regularly.', 'tablepress' ); ?>
			<?php _e( 'Select the table, the desired export format and (for CSV only) a delimiter.', 'tablepress' ); ?>
			<?php _e( 'You may choose to download the export file. Otherwise it will be shown on this page.', 'tablepress' ); ?>
			<?php _e( 'Be aware that only the table data, but no options or settings are exported with this method.', 'tablepress' ); ?>
			<?php printf( __( 'To backup all tables, including their settings, at once, use the &quot;%s&quot; button in the &quot;%s&quot;.', 'tablepress' ), __( 'Create and Download Dump File', 'tablepress' ), __( 'Plugin Options', 'tablepress' ) ); ?>
		</p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_no_tables( $data, $box ) {
		$add_url = TablePress::url( array( 'action' => 'add' ) );
		$import_url = TablePress::url( array( 'action' => 'import' ) );
		?>
		<p><?php _e( 'No tables found.', 'tablepress' ); ?></p>
		<p><?php printf( __( 'You should <a href="%s">add</a> or <a href="%s">import</a> a table to get started!', 'tablepress' ), $add_url, $import_url ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_export_form( $data, $box ) {
?>
<table class="tablepress-postbox-table fixed">
<tbody>
	<tr>
		<th class="column-1 top-align" scope="row"><label for="tables-export"><?php _e( 'Tables to Export', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<input type="hidden" name="export[tables_list]" id="tables-export-list" value="" />
			<?php
				$select_size = $data['tables_count'] + 1; // to show at least one empty row in the select
				$select_size = max( $select_size, 3 );
				$select_size = min( $select_size, 12 );
				$size_multiple = ( $data['zip_support_available'] ) ? " size=\"{$select_size}\" multiple=\"multiple\"" : '';
			?>
			<select id="tables-export" name="export[tables][]"<?php echo $size_multiple; ?>>
			<?php
				foreach ( $data['tables'] as $table ) {
					if ( '' == trim( $table['name'] ) )
						$table['name'] = __( '(no name)', 'tablepress' );
					$text = esc_html( sprintf( __( 'ID %1$s: %2$s ', 'tablepress' ), $table['id'], $table['name'] ) );
					$selected = selected( true, in_array( $table['id'], $data['export_ids'] ), false );
					echo "<option{$selected} value=\"{$table['id']}\">{$text}</option>";
				}
			?>
			</select><br/>
			<?php
				if ( $data['zip_support_available'] )
					echo '<span class="description">' . __( 'You can select multiple tables by holding down the &quot;Ctrl&quot; key (Windows) or the &quot;Command&quot; key (Mac).', 'tablepress' ) . '</span>';
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
	<tr>
		<th class="column-1" scope="row"><?php _e( 'ZIP file', 'tablepress' ); ?>:</th>
		<td class="column-2">
		<?php
			if ( $data['zip_support_available'] ) {
		?>
			<input type="checkbox" id="tables-export-zip-file" name="export[zip_file]" value="true" />
			<label for="tables-export-zip-file" style="vertical-align: top;"><?php _e( 'Create a ZIP archive.', 'tablepress' ); ?></label>
			<span id="tables-export-zip-file-description" class="description hide-if-js"><?php _e( '(Mandatory if more than one table is selected.)', 'tablepress' ); ?></span>
		<?php
			} else {
				_e( 'Note: Support for ZIP file creation seems not to be available on this server.' );
			}
		?>
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
		return 'Help for the Export Table screen';
	}

} // class TablePress_Export_View