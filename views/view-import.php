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
	 * @var string[]
	 */
	protected $wp_pointers = array( 'tp20_import_drag_drop_detect_format' );

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

		$this->admin_page->enqueue_style( 'jsuites' );
		$this->admin_page->enqueue_style( 'import', array( 'tablepress-jsuites' ) );
		$this->admin_page->enqueue_script( 'jsuites' );
		$this->admin_page->enqueue_script( 'import', array( 'tablepress-jsuites' ) );

		$this->process_action_messages( array(
			'error_import' => __( 'Error: The import failed.', 'tablepress' ),
		) );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		$this->add_meta_box( 'import-form', __( 'Import Tables', 'tablepress' ), array( $this, 'postbox_import_form' ), 'normal' );
		$screen = get_current_screen();
		add_filter( "postbox_classes_{$screen->id}_tablepress_{$this->action}-import-form", array( $this, 'postbox_classes' ) );
		$this->add_meta_box( 'tables-auto-import', __( 'Automatic Periodic Table Import', 'tablepress' ), array( $this, 'postbox_auto_import' ), 'additional' );
	}

	/**
	 * Print the screen head text.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_head( array $data, array $box ): void {
		?>
		<p>
			<?php _e( 'TablePress can import tables from common spreadsheet applications, like XLSX files from Excel, or CSV, ODS, HTML, and JSON files.', 'tablepress' ); ?>
		</p>
		<p>
			<?php _e( 'To import tables, select and enter the import source in the following form.', 'tablepress' ); ?>
			<?php _e( 'You can also choose to import it as a new table, to replace an existing table, or to append the rows to an existing table.', 'tablepress' ); ?>
		</p>
		<?php
	}

	/**
	 * Prints the content of the "Import Tables" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_import_form( array $data, array $box ): void {
		$script_data = array(
			'tables'                 => $this->admin_page->convert_to_json_parse_output( $data['tables'] ),
			'importSource'           => $this->admin_page->convert_to_json_parse_output( $data['import_source'] ),
			'importType'             => $this->admin_page->convert_to_json_parse_output( $data['import_type'] ),
			'importUrl'              => $this->admin_page->convert_to_json_parse_output( esc_url( $data['import_url'] ) ),
			'importServer'           => $this->admin_page->convert_to_json_parse_output( $data['import_server'] ),
			'importFormField'        => $this->admin_page->convert_to_json_parse_output( $data['import_form-field'] ),
			'importExistingTable'    => $this->admin_page->convert_to_json_parse_output( $data['import_existing_table'] ),
			'zipSupportAvailable'    => $this->admin_page->convert_to_json_parse_output( $data['zip_support_available'] ),
			'showImportSourceServer' => ( ( ! is_multisite() && current_user_can( 'manage_options' ) ) || is_super_admin() ) ? 'true' : 'false',
			'legacyImport'           => $this->admin_page->convert_to_json_parse_output( $data['legacy_import'] ),
		);

		echo "<script>\n";
		echo "window.tp = window.tp || {};\n";
		echo "tp.import = {};\n";
		foreach ( $script_data as $variable => $value ) {
			echo "tp.import.{$variable} = {$value};\n";
		}
		echo "</script>\n";

		echo '<div id="tablepress-import-screen"></div>';
	}

	/**
	 * Adds the "no-validation-highlighting" class to the "Import Tables" post meta box.
	 *
	 * @since 2.2.0
	 *
	 * @param string[] $classes The array of postbox classes.
	 * @return string[] The modified array of postbox classes.
	 */
	public function postbox_classes( array $classes ): array {
		$classes[] = 'no-validation-highlighting';
		return $classes;
	}

	/**
	 * Prints the content of the "Automatic Periodic Table Import Screen" post meta box.
	 *
	 * @since 2.2.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 *
	 * @phpstan-ignore-next-line (PHPStan would like to see a type hint.)
	 */
	public function postbox_auto_import( /* array */ $data, /* array */ $box ) /* : void */ {
		// Don't use type hints in the method declaration, as the method is extended in the TablePress Table Auto Update Extension which is no longer updated.

		if ( tb_tp_fs()->is_free_plan() ) :
			?>
			<p style="font-size:14px;">
				<span class="dashicons dashicons-info-outline"></span>
				<strong><?php _e( 'Pro Tip:', 'tablepress' ); ?></strong>
				<?php printf( __( 'You can automate the import of tables from URLs or server files with the <a href="%1$s">“%2$s” premium feature</a>!', 'tablepress' ), 'https://tablepress.org/modules/automatic-periodic-table-import/?utm_source=plugin&utm_medium=textlink&utm_content=import-screen', __( 'Automatic Periodic Table Import', 'tablepress' ) ); ?>
			</p>
			<?php
		endif;
	}

	/**
	 * Sets the content for the WP feature pointer about the drag and drop import and format detection on the "Import" screen.
	 *
	 * @since 2.0.0
	 */
	public function wp_pointer_tp20_import_drag_drop_detect_format(): void {
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
