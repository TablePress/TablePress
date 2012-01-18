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

		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );

		if ( $data['tables_count'] > 0 ) {
			$this->add_text_box( 'export-form', array( &$this, 'textbox_export_form' ), 'normal' );
			$this->data['submit_button_caption'] = __( 'Export Table', 'tablepress' );
			$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'normal' );
			if ( ! empty( $data['export_output'] ) )
				$this->add_text_box( 'export-output', array( &$this, 'textbox_export_output' ), 'normal' );
		} else {
			$this->add_text_box( 'no-tables', array( &$this, 'textbox_no_tables' ), 'normal' );
		}
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_head( $data, $box ) {
		?>
		<p><?php _e( 'It is recommended to export and backup the data of important tables regularly.', 'tablepress' ); ?> <?php _e( 'Select the table, the desired export format and (for CSV only) a delimiter.', 'tablepress' ); ?> <?php _e( 'You may choose to download the export file. Otherwise it will be shown on this page.', 'tablepress' ); ?><br/><?php _e( 'Be aware that only the table data, but no options or settings are exported with this method.', 'tablepress' ); ?><br/><?php printf( __( 'To backup all tables, including their settings, at once, use the &quot;%s&quot; button in the &quot;%s&quot;.', 'tablepress' ), __( 'Create and Download Dump File', 'tablepress' ), __( 'Plugin Options', 'tablepress' ) ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_no_tables( $data, $box ) {
		$add_url = TablePress::url( array( 'action' => 'add' ), false );
		$import_url = TablePress::url( array( 'action' => 'import' ), false );
		?>
		<p><?php _e( 'No tables were found.', 'tablepress' ); ?></p>
		<p><?php printf( __( 'You should <a href="%s">add</a> or <a href="%s">import</a> a table to get started!', 'tablepress' ), $add_url, $import_url ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_export_form( $data, $box ) {
		$export_id = '';//$data['table_id'];

		?>
		<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="table_id"><?php _e( 'Select Table to Export', 'tablepress' ); ?>:</label></th>
			<td><select id="table_id" name="table_id">
			<?php
				foreach ( $data['tables'] as $table ) {
					$id = esc_attr( $table['id'] );
					$name = esc_html( $table['name'] );
					$text = sprintf( __( '%1$s (ID %2$s)', 'tablepress' ), $name, $id );
					$selected = selected( $id, $export_id, false );
					echo "<option{$selected} value=\"{$id}\">{$text}</option>";
				}
			?>
			</select></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="export_format"><?php _e( 'Select Export Format', 'tablepress' ); ?>:</label></th>
			<td><select id="export_format" name="export_format">
			<?php
				/*
				$export_formats = $this->export_instance->export_formats;
				foreach ( $export_formats as $export_format => $longname ) {
					echo "<option" . ( ( isset( $_POST['export_format'] ) && $export_format == $_POST['export_format'] ) ? ' selected="selected"': '' ) . " value=\"{$export_format}\">{$longname}</option>";
				}
				*/
			?>
			</select></td>
		</tr>
		<tr valign="top" class="tr-export-delimiter">
			<th scope="row"><label for="delimiter"><?php _e( 'Select Delimiter to use', 'tablepress' ); ?>:</label></th>
			<td><select id="delimiter" name="delimiter">
			<?php
				/*
				$delimiters = $this->export_instance->delimiters;
				foreach ( $delimiters as $delimiter => $longname ) {
					echo "<option" . ( ( isset( $_POST['delimiter'] ) && $delimiter == $_POST['delimiter'] ) ? ' selected="selected"': '' ) . " value=\"{$delimiter}\">{$longname}</option>";
				}
				*/
			?>
			</select> <small>(<?php _e( 'Only needed for CSV export.', 'tablepress' ); ?>)</small></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Download file', 'tablepress' ); ?>:</th>
			<td><input type="checkbox" name="download_export_file" id="download_export_file" value="true"<?php //echo ( isset( $_POST['submit'] ) && !isset( $_POST['download_export_file'] ) ) ? '' : ' checked="checked"'; ?> /> <label for="download_export_file"><?php _e( 'Yes, I want to download the export file.', 'tablepress' ); ?></label></td>
		</tr>
		</table>
		<?php
	}
	
	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_export_output( $data, $box ) {
		_e( 'Export Output:', 'tablepress' );
		?>
		<br/>
		<textarea rows="15" cols="40" class="large-text"><?php echo esc_html( $data['export_output'] ); ?></textarea>
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