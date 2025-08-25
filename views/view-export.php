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
	 * @param string               $action Action for this view.
	 * @param array<string, mixed> $data   Data for this view.
	 */
	#[\Override]
	public function setup( /* string */ $action, array $data ) /* : void */ {
		// Don't use type hints in the method declaration to prevent PHP errors, as the method is inherited.

		parent::setup( $action, $data );

		$this->add_text_box( 'no-javascript', array( $this, 'textbox_no_javascript' ), 'header' );

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
	 * Prints the screen head text.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_head( array $data, array $box ): void {
		?>
		<p>
			<?php _e( 'Exporting a table allows you to use it in other programs, like spreadsheets applications.', 'tablepress' ); ?>
			<?php _e( 'Regularly exporting tables is also recommended as a backup of your data.', 'tablepress' ); ?>
		</p>
		<p>
			<?php _e( 'To export, select the tables and the desired export format.', 'tablepress' ); ?>
			<?php _e( 'If you choose more than one table, the exported files will automatically be stored in a ZIP archive file.', 'tablepress' ); ?>
			<br>
			<?php _e( 'Be aware that for the CSV and HTML formats only the table data, but no table options are exported!', 'tablepress' ); ?>
			<?php _e( 'For the JSON format, the table data and the table options are exported.', 'tablepress' ); ?>
		</p>
		<?php
	}

	/**
	 * Prints the content of the "No tables found" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_no_tables( array $data, array $box ): void {
		$add_url = TablePress::url( array( 'action' => 'add' ) );
		$import_url = TablePress::url( array( 'action' => 'import' ) );
		?>
		<p><?php _e( 'No tables found.', 'tablepress' ); ?></p>
		<p>
			<?php
			/* translators: %1$s: URL to add table page, %2$s: URL to import table page */
			printf( __( 'You should <a href="%1$s">add</a> or <a href="%2$s">import</a> a table to get started!', 'tablepress' ), $add_url, $import_url );
			?>
		</p>
		<?php
	}

	/**
	 * Prints the content of the "Export Tables" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_export_form( array $data, array $box ): void {
		$this->print_script_data_json(
			'export',
			array(
				'tables'              => $data['tables'],
				'exportFormats'       => $data['export_formats'],
				'csvDelimiters'       => $data['csv_delimiters'],
				'zipSupportAvailable' => $data['zip_support_available'],
				'selectedTables'      => $data['export_ids'],
				'exportFormat'        => $data['export_format'],
				'csvDelimiter'        => $data['csv_delimiter'],
			),
		);

		echo '<div id="tablepress-export-screen"></div>';
	}

} // class TablePress_Export_View
