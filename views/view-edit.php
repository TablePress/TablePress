<?php
/**
 * Edit Table View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Edit Table View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Edit_View extends TablePress_View {

	/**
	 * List of WP feature pointers for this view.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	protected array $wp_pointers = array( 'tp20_edit_context_menu', 'tp21_edit_screen_options' );

	/**
	 * Sets up the view with data and do things that are specific for this view.
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

		if ( isset( $data['table']['is_corrupted'] ) && $data['table']['is_corrupted'] ) {
			$this->add_text_box( 'table-corrupted', array( $this, 'textbox_corrupted_table' ), 'header' );
			return;
		}

		$this->process_action_messages( array(
			'success_add'    => __( 'The table was added successfully.', 'tablepress' ),
			'success_copy'   => _n( 'The table was copied successfully.', 'The tables were copied successfully.', 1, 'tablepress' ) . ' ' . sprintf( __( 'You are now seeing the copied table, which has the table ID &#8220;%s&#8221;.', 'tablepress' ), esc_html( $data['table']['id'] ) ),
			'success_import' => __( 'The table was imported successfully.', 'tablepress' ),
			'error_delete'   => __( 'Error: The table could not be deleted.', 'tablepress' ),
		) );

		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_style( 'editor-buttons' );
		wp_enqueue_script( 'wpdialogs' ); // For the Advanced Editor, Table Preview, and Help Boxes.
		add_filter( 'media_view_strings', array( $this, 'change_media_view_strings' ) );
		wp_enqueue_media();
		wp_enqueue_script( 'wplink' ); // JS for the "Insert Link" button.
		$this->admin_page->enqueue_style( 'jspreadsheet' );
		$this->admin_page->enqueue_style( 'jsuites', array( 'tablepress-jspreadsheet' ) );
		$this->admin_page->enqueue_style( 'edit', array( 'tablepress-jspreadsheet', 'tablepress-jsuites' ) );
		if ( ! TABLEPRESS_IS_PLAYGROUND_PREVIEW && tb_tp_fs()->is_free_plan() ) {
			$this->admin_page->enqueue_style( 'edit-features', array( 'tablepress-edit' ) );
		}
		$this->admin_page->enqueue_script( 'jspreadsheet' );
		$this->admin_page->enqueue_script( 'jsuites', array( 'tablepress-jspreadsheet' ) );
		$this->admin_page->enqueue_script( 'edit', array( 'tablepress-jspreadsheet', 'tablepress-jsuites', 'jquery-core' ) );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		$this->add_text_box( 'buttons-1', array( $this, 'textbox_buttons' ), 'normal' );
		$this->add_meta_box( 'table-information', __( 'Table Information', 'tablepress' ), array( $this, 'postbox_table_information' ), 'normal' );
		$this->add_meta_box( 'table-data', __( 'Table Content', 'tablepress' ), array( $this, 'postbox_table_data' ), 'normal' );
		$this->add_meta_box( 'table-manipulation', __( 'Table Manipulation', 'tablepress' ), array( $this, 'postbox_table_manipulation' ), 'normal' );
		$this->add_meta_box( 'table-options', __( 'Table Options', 'tablepress' ), array( $this, 'postbox_table_options' ), 'normal' );
		$this->add_meta_box( 'datatables-features', __( 'Table Features for Site Visitors', 'tablepress' ), array( $this, 'postbox_datatables_features' ), 'normal' );
		$this->add_text_box( 'hidden-containers', array( $this, 'textbox_hidden_containers' ), 'additional' );
		$this->add_text_box( 'buttons-2', array( $this, 'textbox_buttons' ), 'additional' );
		$this->add_text_box( 'other-actions', array( $this, 'textbox_other_actions' ), 'submit' );

		add_filter( 'screen_settings', array( $this, 'add_screen_options_output' ), 10, 2 );
	}

	/**
	 * Changes the Media View string "Insert into post" to "Insert into Table".
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $strings Current set of Media View strings.
	 * @return array<string, string> Changed Media View strings.
	 */
	public function change_media_view_strings( array $strings ): array {
		$strings['insertIntoPost'] = __( 'Insert into Table', 'tablepress' );
		return $strings;
	}

	/**
	 * Adds custom screen options to the screen.
	 *
	 * @since 2.1.0
	 *
	 * @param string    $screen_settings Screen settings.
	 * @param WP_Screen $screen          WP_Screen object.
	 * @return string Extended Screen settings.
	 */
	public function add_screen_options_output( /* string */ $screen_settings, WP_Screen $screen ): string {
		// Don't use a type hint for the `$screen_settings` argument as many WordPress plugins seem to be returning `null` in their filter hook handlers.

		// Protect against other plugins not returning a string for the `$screen_settings` argument.
		if ( ! is_string( $screen_settings ) ) { // @phpstan-ignore function.alreadyNarrowedType (The `is_string()` check is needed as the input is coming from a filter hook.)
			$screen_settings = '';
		}

		$screen_settings = '<fieldset id="tablepress-screen-options" class="screen-options">';
		$screen_settings .= '<legend>' . __( 'Table editor settings', 'tablepress' ) . '</legend>';
		$screen_settings .= '<p>' . __( 'Adjust the default size of the table cells in the table editor below.', 'tablepress' ) . ' ' . __( 'Cells with many lines of text will expand to their full height when they are edited.', 'tablepress' ) . '</p>';
		$screen_settings .= '<p><em>' . __( 'Please note: These settings only influence the table editor view on this screen, but not the table that the site visitor sees!', 'tablepress' ) . '</em></p>';
		$screen_settings .= '<div>';
		$screen_settings .= '<label for="table_editor_column_width">' . __( 'Default column width:', 'tablepress' ) . '</label> ';
		$input = '<input type="number" id="table_editor_column_width" class="small-text" value="' . esc_attr( TablePress::$model_options->get( 'table_editor_column_width' ) ) . '" min="30" max="9999">';
		$screen_settings .= sprintf( __( '%s pixels', 'tablepress' ), $input );
		$screen_settings .= '</div>';
		$screen_settings .= '<div style="margin-top: 6px;">';
		$screen_settings .= '<label for="table_editor_line_clamp">' . __( 'Maximum visible lines of text:', 'tablepress' ) . '</label> ';
		$input = '<input type="number" id="table_editor_line_clamp" class="tiny-text" value="' . esc_attr( TablePress::$model_options->get( 'table_editor_line_clamp' ) ) . '" min="0" max="999">';
		$screen_settings .= sprintf( __( '%s lines', 'tablepress' ), $input );
		$screen_settings .= '</div>';
		$screen_settings .= '</fieldset>';
		return $screen_settings;
	}

	/**
	 * Renders the current view.
	 *
	 * In comparison to the parent class method, this contains handling for the no-js case and adjusts the HTML code structure.
	 *
	 * @since 2.0.0
	 */
	#[\Override]
	public function render(): void {
		$this->print_javascript_data();

		?>
		<div id="tablepress-page" class="wrap">
		<form>
		<?php
			$this->print_nav_tab_menu();
		?>
		<div id="tablepress-body">
		<hr class="wp-header-end">
		<?php
		// Print all header messages.
		foreach ( $this->header_messages as $message ) {
			echo $message;
		}

		$this->do_text_boxes( 'header' );
		?>
		<div id="poststuff" class="hide-if-no-js">
			<div id="post-body" class="metabox-holder columns-1">
				<div id="postbox-container-2" class="postbox-container">
					<?php
					$this->do_text_boxes( 'normal' );
					$this->do_meta_boxes( 'normal' );

					$this->do_text_boxes( 'additional' );
					$this->do_meta_boxes( 'additional' );

					$this->do_text_boxes( 'submit' );

					$this->do_text_boxes( 'side' );
					$this->do_meta_boxes( 'side' );
					?>
				</div>
			</div>
			<br class="clear">
		</div>
		</form>
		</div>
		</div>
		<?php
	}

	/**
	 * Overrides parent class method, as the nonces for this view are generated in the JS code.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	#[\Override]
	protected function action_nonce_field( array $data, array $box ): void {
		// Intentionally left empty. Nonces for this view are generated in postbox_table_data().
	}

	/**
	 * Prints the table data for use in JS code and the main React container.
	 *
	 * @since 3.0.0
	 */
	public function print_javascript_data(): void {
		echo "<script>\n";
		echo "window.tp = window.tp || {};\n";

		echo "tp.nonces = {};\n";
		echo "tp.nonces.edit_table = '" . wp_create_nonce( TablePress::nonce( $this->action, $this->data['table']['id'] ) ) . "';\n";
		echo "tp.nonces.preview_table = '" . wp_create_nonce( TablePress::nonce( 'preview_table', $this->data['table']['id'] ) ) . "';\n";
		echo "tp.nonces.copy_table = '" . wp_create_nonce( TablePress::nonce( 'copy_table', $this->data['table']['id'] ) ) . "';\n";
		echo "tp.nonces.delete_table = '" . wp_create_nonce( TablePress::nonce( 'delete_table', $this->data['table']['id'] ) ) . "';\n";
		echo "tp.nonces.screen_options = '" . wp_create_nonce( TablePress::nonce( 'screen_options' ) ) . "';\n";

		echo "tp.table = {};\n";
		echo "tp.table.shortcode = '" . esc_js( TablePress::$shortcode ) . "';\n";
		echo "tp.table.id = '{$this->data['table']['id']}';\n";

		// Add the table meta data.
		$this->data['table']['meta'] = array(
			'newId'        => $this->data['table']['id'],
			'name'         => $this->data['table']['name'],
			'description'  => $this->data['table']['description'],
			'lastModified' => TablePress::format_datetime( $this->data['table']['last_modified'] ),
			'lastEditor'   => TablePress::get_user_display_name( $this->data['table']['options']['last_editor'] ),
		);

		// JSON-encode array items separately to save some PHP memory.
		foreach ( array( 'meta', 'data', 'options', 'visibility' ) as $item ) {
			$json = $this->admin_page->convert_to_json_parse_output( $this->data['table'][ $item ] );
			printf( 'tp.table.%1$s = %2$s;' . "\n", $item, $json );
		}

		echo "tp.screen_options = {};\n";
		echo 'tp.screen_options.table_editor_column_width = ' . absint( TablePress::$model_options->get( 'table_editor_column_width' ) ) . ";\n";
		echo 'tp.screen_options.showCustomCommands = ' . ( current_user_can( 'unfiltered_html' ) ? 'true' : 'false' ) . ";\n";
		echo "tp.screen_options.optionsUrl = '" . TablePress::url( array( 'action' => 'options' ) ) . "';\n";
		echo 'tp.screen_options.currentUserCanEditTableId = ' . ( current_user_can( 'tablepress_edit_table_id', $this->data['table']['id'] ) ? 'true' : 'false' ) . ";\n";
		echo 'tp.screen_options.currentUserCanPreviewTable = ' . ( current_user_can( 'tablepress_preview_table', $this->data['table']['id'] ) ? 'true' : 'false' ) . ";\n";
		echo "tp.screen_options.previewUrl = '" . str_replace( '&amp;', '&', TablePress::url( array( 'action' => 'preview_table', 'item' => $this->data['table']['id'], 'return' => 'edit', 'return_item' => $this->data['table']['id'] ), true, 'admin-post.php' ) ) . "';\n";
		echo "</script>\n";

		echo '<div id="tablepress-edit-screen"></div>'; // React container.
	}

	/**
	 * Prints the content of the "Table Information" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_table_information( array $data, array $box ): void {
		echo '<div id="tablepress-table-information-section"></div>';

		if ( ! TABLEPRESS_IS_PLAYGROUND_PREVIEW && tb_tp_fs()->is_free_plan() ) :
			?>
			<div class="postbox premium-features">
				<div>
					<div>
						<div class="postbox-header">
							<h2><span class="dashicons dashicons-heart"></span> <?php _e( 'TablePress has more to offer!', 'tablepress' ); ?></h2>
						</div>
						<div class="inside">
						<?php
							TablePress::init_modules();
							$random_module_slug = array_rand( TablePress::$modules );
							$feature_module = TablePress::$modules[ $random_module_slug ];
							$module_url = esc_url( "https://tablepress.org/modules/{$random_module_slug}/?utm_source=plugin&utm_medium=textlink&utm_content=edit-screen" );

							echo '<strong>' . __( 'Supercharge your tables with exceptional features:', 'tablepress' ) . '</strong>';
							echo '<h3><a href="' . $module_url . '">' . $feature_module['name'] . '</a></h3>';
							echo '<span>' . $feature_module['description'] . ' <a href="' . $module_url . '">' . __( 'Read more!', 'tablepress' ) . '</a></span>';
						?>
						</div>
					</div>
					<div class="buttons">
						<a href="https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=edit-screen" class="tablepress-button">
							<span><?php _e( 'Compare the TablePress premium versions', 'tablepress' ); ?></span>
							<span class="dashicons dashicons-arrow-right-alt"></span>
						</a>
					</div>
				</div>
			</div>
			<?php
		endif;
	}

	/**
	 * Prints the content of the "Table Content" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_table_data( array $data, array $box ): void {
		$css_variables = '--table-editor-line-clamp:' . absint( TablePress::$model_options->get( 'table_editor_line_clamp' ) ) . ';';
		$css_variables = esc_attr( $css_variables );
		echo "<div id=\"table-editor\" style=\"{$css_variables}\"></div>";
	}

	/**
	 * Prints the content of the "Table Manipulation" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_table_manipulation( array $data, array $box ): void {
		echo '<div id="tablepress-table-manipulation-section"></div>';
	}

	/**
	 * Prints the container for the "Preview" and "Save Changes" buttons.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_buttons( array $data, array $box ): void {
		echo '<div id="tablepress-' . $box['id'] . '-section"></div>';
	}

	/**
	 * Prints the "Delete Table" and "Export Table" buttons.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_other_actions( array $data, array $box ): void {
		$user_can_copy_table = current_user_can( 'tablepress_copy_table', $data['table']['id'] );
		$user_can_export_table = current_user_can( 'tablepress_export_table', $data['table']['id'] );
		$user_can_delete_table = current_user_can( 'tablepress_delete_table', $data['table']['id'] );

		if ( ! $user_can_copy_table && ! $user_can_export_table && ! $user_can_delete_table ) {
			return;
		}

		echo '<p class="submit">';
		echo __( 'Other Actions', 'tablepress' ) . ':&nbsp; ';
		if ( $user_can_copy_table ) {
			echo '<a href="' . esc_url( TablePress::url( array( 'action' => 'copy_table', 'item' => $data['table']['id'], 'return' => 'edit' ), true, 'admin-post.php' ) ) . '" class="components-button is-secondary is-compact button-copy">' . __( 'Copy Table', 'tablepress' ) . '</a> ';
		}
		if ( $user_can_export_table ) {
			echo '<a href="' . esc_url( TablePress::url( array( 'action' => 'export', 'table_id' => $data['table']['id'] ) ) ) . '" class="components-button is-secondary is-compact button-export">' . __( 'Export Table', 'tablepress' ) . '</a> ';
		}
		if ( $user_can_delete_table ) {
			echo '<a href="' . esc_url( TablePress::url( array( 'action' => 'delete_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' ) ) . '" class="components-button is-secondary is-compact button-delete delete-link">' . __( 'Delete Table', 'tablepress' ) . '</a>';
		}
		echo '</p>';
	}

	/**
	 * Prints the hidden containers for the Preview.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_hidden_containers( array $data, array $box ): void {
		?>
<div class="hidden-container">
	<div id="advanced-editor">
		<label id="advanced-editor-label" for="advanced-editor-content" class="screen-reader-text"><?php esc_html_e( 'Advanced Editor', 'tablepress' ); ?></label>
		<?php
		$wp_editor_options = array(
			'textarea_rows' => 10,
			'tinymce'       => false,
			'quicktags'     => array(
				'buttons' => 'strong,em,link,del,ins,img,code,spell,close',
			),
		);
		wp_editor( '', 'advanced-editor-content', $wp_editor_options );
		?>
	</div>
</div>
<div id="preview-container" class="hidden-container">
	<div id="table-preview">
		<iframe id="table-preview-iframe" src="about:blank"></iframe>
	</div>
</div>
<div class="hidden-container">
	<textarea id="textarea-insert-helper" class="hidden"></textarea>
</div>
<div id="help-box-combine-cells" class="help-box hidden-container" title="<?php esc_attr_e( 'Help on combining cells', 'tablepress' ); ?>" data-height="420" data-width="500">
		<?php
		echo '<p>' . __( 'Table cells can span across more than one column or row.', 'tablepress' ) . '</p>';
		echo '<p>' . __( 'Combining consecutive cells within the same row is called &#8220;colspanning&#8221;.', 'tablepress' )
		. ' ' . __( 'Combining consecutive cells within the same column is called &#8220;rowspanning&#8221;.', 'tablepress' ) . '</p>';
		echo '<p>' . sprintf( __( 'To combine adjacent cells, select the desired cells and click the “%s” button or use the context menu.', 'tablepress' ), __( 'Combine/Merge', 'tablepress' ) )
		. ' ' . __( 'The corresponding keywords, <code>#colspan#</code> and <code>#rowspan#</code>, will then be added for you.', 'tablepress' ) . '</p>';
		echo '<p><strong>' . __( 'Be aware that the Table Features for Site Visitors, like sorting, filtering, and pagination, will not work in tables which have combined cells in their body rows.', 'tablepress' ) . '</strong>'
		. ' ' . __( 'It is however possible to use these features in tables that have combined cells in the table header or footer rows, to allow for creating complex header and footer layouts.', 'tablepress' ) . '</p>';
		?>
</div>
		<?php
	}

	/**
	 * Prints the content of the "Table Options" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_table_options( array $data, array $box ): void {
		echo '<div id="tablepress-table-options-section"></div>';
	}

	/**
	 * Prints the content of the "Table Features for Site Visitors" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_datatables_features( array $data, array $box ): void {
		echo '<div id="tablepress-datatables-features-section"></div>';
	}

	/**
	 * Prints a notification about a corrupted table.
	 *
	 * @since 1.4.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_corrupted_table( array $data, array $box ): void {
		?>
		<div class="notice notice-error notice-large">
			<h3><em>
				<?php _e( 'Attention: Unfortunately, an error occurred.', 'tablepress' ); ?>
			</em></h3>
			<p>
				<?php
					printf( __( 'The internal data of table &#8220;%1$s&#8221; (ID %2$s) is corrupted.', 'tablepress' ), esc_html( $data['table']['name'] ), esc_html( $data['table']['id'] ) );
					echo ' ';
					printf( __( 'The following error was registered: %s.', 'tablepress' ), '<code>' . esc_html( $data['table']['json_error'] ) . '</code>' );
				?>
			</p>
			<p>
				<?php
					_e( 'Because of this error, the table can not be edited at this time, to prevent possible further data loss.', 'tablepress' );
					echo ' ';
					printf( __( 'Please see the <a href="%s">TablePress FAQ page</a> for further instructions.', 'tablepress' ), 'https://tablepress.org/faq/corrupted-tables/' );
				?>
			</p>
			<p>
				<?php echo '<a href="' . esc_url( TablePress::url( array( 'action' => 'list' ) ) ) . '" class="components-button is-secondary is-compact">' . __( 'Back to the List of Tables', 'tablepress' ) . '</a>'; ?>
			</p>
		</div>
		<?php
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
		echo '<p>';
		_e( 'To edit the content or modify the structure of this table, use the input fields and buttons below.', 'tablepress' );
		echo ' ';
		// Show the instructions string depending on whether the Block Editor is used on the site or not.
		if ( $data['site_uses_block_editor'] ) {
			printf( __( 'To insert a table into a post or page, add a “%1$s” block in the block editor and select the desired table.', 'tablepress' ), __( 'TablePress table', 'tablepress' ) );
		} else {
			_e( 'To insert a table into a post or page, paste its Shortcode at the desired place in the editor.', 'tablepress' );
		}
		echo '</p>';
	}

	/**
	 * Sets the content for the WP feature pointer about the drag and drop and sort on the "Edit" screen.
	 *
	 * @since 2.0.0
	 */
	public function wp_pointer_tp20_edit_context_menu(): void {
		$content  = '<h3>' . __( 'TablePress feature: Context menu', 'tablepress' ) . '</h3>';
		$content .= '<p>' . __( 'Did you know?', 'tablepress' ) . ' ' . __( 'Right-clicking the table content fields will open a context menu for quick access to common editing tools.', 'tablepress' ) . '</p>';

		$this->admin_page->print_wp_pointer_js(
			'tp20_edit_context_menu',
			'#table-editor',
			array(
				'content'  => $content,
				'position' => array( 'edge' => 'bottom', 'align' => 'center' ),
			),
		);
	}

	/**
	 * Sets the content for the WP feature pointer about the screen options for changing the cell size.
	 *
	 * @since 2.1.0
	 */
	public function wp_pointer_tp21_edit_screen_options(): void {
		$content  = '<h3>' . __( 'TablePress feature: Column width and row height of the table editor', 'tablepress' ) . '</h3>';
		$content .= '<p>' . __( 'Did you know?', 'tablepress' ) . ' ' . sprintf( __( 'You can change the default cell size for the table editor on this “Edit” screen in the “%s”.', 'tablepress' ), __( 'Screen Options', 'default' ) ) . '</p>';

		$this->admin_page->print_wp_pointer_js(
			'tp21_edit_screen_options',
			'#screen-options-link-wrap',
			array(
				'content'      => $content,
				'position'     => array( 'edge' => 'top', 'align' => 'right' ),
				'pointerClass' => 'wp-pointer pointer-tp21_edit_screen_options',
			),
		);
	}

} // class TablePress_Edit_View
