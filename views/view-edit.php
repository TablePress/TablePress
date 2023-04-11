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
	 * @var array
	 */
	protected $wp_pointers = array( 'tp20_edit_context_menu', 'tp21_edit_screen_options' );

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
		if ( tb_tp_fs()->is_free_plan() ) {
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
	 * Change Media View string "Insert into post" to "Insert into Table".
	 *
	 * @since 1.0.0
	 *
	 * @param array $strings Current set of Media View strings.
	 * @return array Changed Media View strings.
	 */
	public function change_media_view_strings( array $strings ) {
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
	public function add_screen_options_output( $screen_settings, $screen ) {
		$screen_settings = '<fieldset id="tablepress-screen-options" class="screen-options">';
		$screen_settings .= '<legend>' . __( 'Table editor settings', 'tablepress' ) . '</legend>';
		$screen_settings .= '<p>' . __( 'Adjust the default size of the table cells in the table editor below.', 'tablepress' ) . ' ' . __( 'Cells with many lines of text will expand to their full height when they are edited.', 'tablepress' ) . '</p>';
		$screen_settings .= '<p><em>' . __( 'Please note: These settings only influence the table editor view on this screen, but not the table that the site visitor sees!', 'tablepress' ) . '</em></p>';
		$screen_settings .= '<div>';
		$screen_settings .= '<label for="table_editor_column_width">' . __( 'Default column width:', 'tablepress' ) . '</label> ';
		$input = '<input type="number" id="table_editor_column_width" class="small-text" value="' . esc_attr( TablePress::$model_options->get( 'table_editor_column_width' ) ) . '" min="30" max="9999" />';
		$screen_settings .= sprintf( __( '%s pixels', 'tablepress' ), $input );
		$screen_settings .= '</div>';
		$screen_settings .= '<div style="margin-top: 6px;">';
		$screen_settings .= '<label for="table_editor_line_clamp">' . __( 'Maximum visible lines of text:', 'tablepress' ) . '</label> ';
		$input = '<input type="number" id="table_editor_line_clamp" class="tiny-text" value="' . esc_attr( TablePress::$model_options->get( 'table_editor_line_clamp' ) ) . '" min="0" max="999" />';
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
	public function render() {
		?>
		<div id="tablepress-page" class="wrap">
		<form>
		<?php
			$this->print_nav_tab_menu();
		?>
		<div id="tablepress-body">
		<hr class="wp-header-end" />
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
			<br class="clear" />
		</div>
		</form>
		</div>
		</div>
		<?php
	}

	/**
	 * Override parent class method, as the nonces for this view are generated in the JS code.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the text box.
	 */
	protected function action_nonce_field( array $data, array $box ) {
		// Intentionally left empty. Nonces for this view are generated in postbox_table_data().
	}

	/**
	 * Print the content of the "Table Information" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_table_information( array $data, array $box ) {
		?>
<table class="tablepress-postbox-table fixed">
	<tr class="bottom-border">
		<th class="column-1" scope="row"><label for="table-id"><?php _e( 'Table ID', 'tablepress' ); ?>:</label></th>
		<td class="column-2">
			<div id="table-id-shortcode-wrapper">
				<input type="text" id="table-id" value="<?php echo esc_attr( $data['table']['id'] ); ?>" title="<?php esc_attr_e( 'The Table ID can only consist of letters, numbers, hyphens (-), and underscores (_).', 'tablepress' ); ?>" pattern="[A-Za-z1-9-_]|[A-Za-z0-9-_]{2,}" required <?php echo ( ! current_user_can( 'tablepress_edit_table_id', $data['table']['id'] ) ) ? 'readonly ' : ''; ?>/>
				<div><label for="table-information-shortcode"><?php _e( 'Shortcode', 'tablepress' ); ?>:</label> <input type="text" id="table-information-shortcode" value="<?php echo esc_attr( '[' . TablePress::$shortcode . " id={$data['table']['id']} /]" ); ?>" readonly /></div>
			</div>
		</td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><label for="table-name"><?php _e( 'Table Name', 'tablepress' ); ?>:</label></th>
		<td class="column-2"><input type="text" id="table-name" class="large-text" value="<?php echo esc_attr( $data['table']['name'] ); ?>" /></td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1 top-align" scope="row"><label for="table-description"><?php _e( 'Description', 'tablepress' ); ?>:</label></th>
		<td class="column-2"><textarea id="table-description" class="large-text" rows="4"><?php echo esc_textarea( $data['table']['description'] ); ?></textarea></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Last Modified', 'tablepress' ); ?>:</th>
		<td class="column-2"><?php printf( __( '%1$s by %2$s', 'tablepress' ), '<span id="last-modified">' . TablePress::format_datetime( $data['table']['last_modified'] ) . '</span>', '<span id="last-editor">' . TablePress::get_user_display_name( $data['table']['options']['last_editor'] ) . '</span>' ); ?></td>
	</tr>
</table>
		<?php
		if ( tb_tp_fs()->is_free_plan() ) :
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
							$module_url = esc_url( "https://tablepress.org/modules/{$random_module_slug}/" );

							echo '<strong>' . __( 'Supercharge your tables with exceptional features:', 'tablepress' ) . '</strong>';
							echo '<h3><a href="' . $module_url . '">' . $feature_module['name'] . '</a></h3>';
							echo '<span>' . $feature_module['description'] . ' <a href="' . $module_url . '">' . __( 'Read more!', 'tablepress' ) . '</a></span>';
						?>
						</div>
					</div>
					<div class="buttons">
						<a href="<?php echo 'https://tablepress.org/premium/'; ?>" class="tablepress-button">
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
	 * Print the content of the "Table Content" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_table_data( array $data, array $box ) {
		echo "<script>\n";
		echo "window.tp = window.tp || {};\n";

		echo "tp.nonces = {};\n";
		echo "tp.nonces.edit_table = '" . wp_create_nonce( TablePress::nonce( $this->action, $data['table']['id'] ) ) . "';\n";
		echo "tp.nonces.preview_table = '" . wp_create_nonce( TablePress::nonce( 'preview_table', $data['table']['id'] ) ) . "';\n";
		echo "tp.nonces.screen_options = '" . wp_create_nonce( TablePress::nonce( 'screen_options' ) ) . "';\n";
		echo "\n";

		echo "tp.table = {};\n";
		echo "tp.table.shortcode = '" . esc_js( TablePress::$shortcode ) . "';\n";
		echo "tp.table.id = '{$data['table']['id']}';\n";
		echo "tp.table.new_id = '{$data['table']['id']}';\n";
		// JSON-encode array items separately to save some PHP memory.
		foreach ( array( 'data', 'options', 'visibility' ) as $item ) {
			$json = $this->admin_page->convert_to_json_parse_output( $data['table'][ $item ] );
			printf( 'tp.table.%1$s = %2$s;' . "\n", $item, $json );
		}

		echo "tp.screen_options = {};\n";
		echo 'tp.screen_options.table_editor_column_width = ' . absint( TablePress::$model_options->get( 'table_editor_column_width' ) ) . ";\n";
		echo "</script>\n";

		$css_variables = '--table-editor-line-clamp:' . absint( TablePress::$model_options->get( 'table_editor_line_clamp' ) ) . ';';
		$css_variables = esc_attr( $css_variables );
		echo "<div id=\"table-editor\" style=\"{$css_variables}\"></div>";
	}

	/**
	 * Print the content of the "Table Manipulation" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_table_manipulation( array $data, array $box ) {
		?>
<table id="tablepress-manipulation-controls" class="tablepress-postbox-table fixed">
	<tr class="bottom-border">
		<td class="column-1">
			<?php _e( 'Selected cells', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button" id="button-insert-link" value="<?php esc_attr_e( 'Insert Link', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$sL', 'keyboard shortcut for Insert Link', 'tablepress' ) ); ?>" />
			<input type="button" class="button" id="button-insert-image" value="<?php esc_attr_e( 'Insert Image', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$sI', 'keyboard shortcut for Insert Image', 'tablepress' ) ); ?>" />
			<input type="button" class="button" id="button-advanced-editor" value="<?php esc_attr_e( 'Advanced Editor', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$sE', 'keyboard shortcut for Advanced Editor', 'tablepress' ) ); ?>" />
		</td>
		<td class="column-2">
			<?php _e( 'Selected cells', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button button-merge-unmerge" data-action="merge" value="<?php esc_attr_e( 'Combine/Merge', 'tablepress' ); ?>" />
			<input type="button" class="button button-show-help-box" value="<?php esc_attr_e( '?', 'tablepress' ); ?>" title="<?php esc_attr_e( 'Help on combining cells', 'tablepress' ); ?>" data-help-box="#help-box-combine-cells" />
		</td>
	</tr>
	<tr class="top-border">
		<td class="column-1">
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button button-insert-duplicate" data-type="rows" data-action="duplicate" value="<?php esc_attr_e( 'Duplicate', 'tablepress' ); ?>" />
			<input type="button" class="button button-insert-duplicate" data-type="rows" data-action="insert" value="<?php esc_attr_e( 'Insert', 'tablepress' ); ?>" />
			<input type="button" class="button button-remove" data-type="rows" value="<?php esc_attr_e( 'Delete', 'tablepress' ); ?>" />
		</td>
		<td class="column-2">
			<?php _e( 'Selected columns', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button button-insert-duplicate" data-type="columns" data-action="duplicate" value="<?php esc_attr_e( 'Duplicate', 'tablepress' ); ?>" />
			<input type="button" class="button button-insert-duplicate" data-type="columns" data-action="insert" value="<?php esc_attr_e( 'Insert', 'tablepress' ); ?>" />
			<input type="button" class="button button-remove" data-type="columns" value="<?php esc_attr_e( 'Delete', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr>
		<td class="column-1">
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button button-move" data-type="rows" data-direction="up" value="<?php esc_attr_e( 'Move up', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$s⇧↑', 'keyboard shortcut for Move up', 'tablepress' ) ); ?>" />
			<input type="button" class="button button-move" data-type="rows" data-direction="down" value="<?php esc_attr_e( 'Move down', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$s⇧↓', 'keyboard shortcut for Move down', 'tablepress' ) ); ?>" />
		</td>
		<td class="column-2">
			<?php _e( 'Selected columns', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button button-move" data-type="columns" data-direction="left" value="<?php esc_attr_e( 'Move left', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$s⇧←', 'keyboard shortcut for Move left', 'tablepress' ) ); ?>" />
			<input type="button" class="button button-move" data-type="columns" data-direction="right" value="<?php esc_attr_e( 'Move right', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$s⇧→', 'keyboard shortcut for Move right', 'tablepress' ) ); ?>" />
		</td>
	</tr>
	<tr class="bottom-border">
		<td class="column-1">
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button button-hide-unhide" data-type="rows" data-action="hide" value="<?php esc_attr_e( 'Hide', 'tablepress' ); ?>" />
			<input type="button" class="button button-hide-unhide" data-type="rows" data-action="unhide" value="<?php esc_attr_e( 'Show', 'tablepress' ); ?>" />
		</td>
		<td class="column-2">
			<?php _e( 'Selected columns', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button button-hide-unhide" data-type="columns" data-action="hide" value="<?php esc_attr_e( 'Hide', 'tablepress' ); ?>" />
			<input type="button" class="button button-hide-unhide" data-type="columns" data-action="unhide" value="<?php esc_attr_e( 'Show', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="top-border">
		<td class="column-1">
			<label><?php printf( __( 'Add %s row(s)', 'tablepress' ), '<input type="number" id="rows-append-number" class="small-text" title="' . esc_attr__( 'This field must contain a positive number.', 'tablepress' ) . '" value="1" min="1" max="99999" required />' ); ?></label>&nbsp;
			<input type="button" class="button button-append" data-type="rows" value="<?php esc_attr_e( 'Add', 'tablepress' ); ?>" />
		</td>
		<td class="column-2">
			<label><?php printf( __( 'Add %s column(s)', 'tablepress' ), '<input type="number" id="columns-append-number" class="small-text" title="' . esc_attr__( 'This field must contain a positive number.', 'tablepress' ) . '" value="1" min="1" max="99999" required />' ); ?></label>&nbsp;
			<input type="button" class="button button-append" data-type="columns" value="<?php esc_attr_e( 'Add', 'tablepress' ); ?>" />
		</td>
	</tr>
</table>
		<?php
	}

	/**
	 * Print the "Preview" and "Save Changes" button.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the text box.
	 */
	public function textbox_buttons( array $data, array $box ) {
		$preview_url = TablePress::url( array( 'action' => 'preview_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' );

		echo '<p id="' . $box['id'] . '-submit" class="submit">';
		if ( current_user_can( 'tablepress_preview_table', $data['table']['id'] ) ) {
			echo '<a href="' . $preview_url . '" class="button button-large button-show-preview" target="_blank" data-shortcut="' . esc_attr( _x( '%1$sP', 'keyboard shortcut for Preview', 'tablepress' ) ) . '">' . __( 'Preview', 'tablepress' ) . '</a>';
		}
		?>
			<input type="button" class="button button-primary button-large button-save-changes" value="<?php esc_attr_e( 'Save Changes', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$sS', 'keyboard shortcut for Save Changes', 'tablepress' ) ); ?>" />
		<?php
		echo '</p>';
	}

	/**
	 * Print the "Delete Table" and "Export Table" buttons.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the text box.
	 */
	public function textbox_other_actions( array $data, array $box ) {
		$user_can_copy_table = current_user_can( 'tablepress_copy_table', $data['table']['id'] );
		$user_can_export_table = current_user_can( 'tablepress_export_table', $data['table']['id'] );
		$user_can_delete_table = current_user_can( 'tablepress_delete_table', $data['table']['id'] );

		if ( ! $user_can_copy_table && ! $user_can_export_table && ! $user_can_delete_table ) {
			return;
		}

		echo '<p class="submit">';
		echo __( 'Other Actions', 'tablepress' ) . ':&nbsp; ';
		if ( $user_can_copy_table ) {
			echo '<a href="' . TablePress::url( array( 'action' => 'copy_table', 'item' => $data['table']['id'], 'return' => 'edit' ), true, 'admin-post.php' ) . '" class="button">' . __( 'Copy Table', 'tablepress' ) . '</a> ';
		}
		if ( $user_can_export_table ) {
			echo '<a href="' . TablePress::url( array( 'action' => 'export', 'table_id' => $data['table']['id'] ) ) . '" class="button">' . __( 'Export Table', 'tablepress' ) . '</a> ';
		}
		if ( $user_can_delete_table ) {
			echo '<a href="' . TablePress::url( array( 'action' => 'delete_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' ) . '" class="button delete-link">' . __( 'Delete Table', 'tablepress' ) . '</a>';
		}
		echo '</p>';
	}

	/**
	 * Print the hidden containers for the Preview.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the text box.
	 */
	public function textbox_hidden_containers( array $data, array $box ) {
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
<div id="help-box-combine-cells" class="help-box hidden-container" title="<?php esc_attr_e( 'Help on combining cells', 'tablepress' ); ?>" data-height="380" data-width="420">
		<?php
		echo '<p>' . __( 'Table cells can span across more than one column or row.', 'tablepress' ) . '</p>';
		echo '<p>' . __( 'Combining consecutive cells within the same row is called &#8220;colspanning&#8221;.', 'tablepress' )
		. ' ' . __( 'Combining consecutive cells within the same column is called &#8220;rowspanning&#8221;.', 'tablepress' ) . '</p>';
		echo '<p>' . sprintf( __( 'To combine adjacent cells, select the desired cells and click the “%s” button or use the context menu.', 'tablepress' ), __( 'Combine/Merge', 'tablepress' ) )
		. ' ' . __( 'The corresponding keywords, <code>#colspan#</code> and <code>#rowspan#</code>, will then be added for you.', 'tablepress' ) . '</p>';
		echo '<p><strong>' . __( 'Be aware that the Table Features for Site Visitors, like sorting, filtering, and pagination, will not work on tables which have combined cells.', 'tablepress' ) . '</strong></p>';
		?>
</div>
		<?php
	}

	/**
	 * Print the content of the "Table Options" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_table_options( array $data, array $box ) {
		?>
<table class="tablepress-postbox-table fixed">
	<tr>
		<th class="column-1" scope="row"><?php _e( 'Table Head Row', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-table_head"><input type="checkbox" id="option-table_head" name="table_head" /> <?php _e( 'The first row of the table is the table header.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'Table Foot Row', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-table_foot"><input type="checkbox" id="option-table_foot" name="table_foot" /> <?php _e( 'The last row of the table is the table footer.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Alternating Row Colors', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-alternating_row_colors"><input type="checkbox" id="option-alternating_row_colors" name="alternating_row_colors" /> <?php _e( 'The background colors of consecutive rows shall alternate.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'Row Hover Highlighting', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-row_hover"><input type="checkbox" id="option-row_hover" name="row_hover" /> <?php _e( 'Highlight a row while the mouse cursor hovers above it by changing its background color.', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><label for="option-print_name"><?php _e( 'Print Table Name', 'tablepress' ); ?></label>:</th>
		<?php
			$position_select = '<select id="option-print_name_position" name="print_name_position">';
			$position_select .= '<option value="above">' . __( 'above', 'tablepress' ) . '</option>';
			$position_select .= '<option value="below">' . __( 'below', 'tablepress' ) . '</option>';
			$position_select .= '</select>';
		?>
		<td class="column-2"><input type="checkbox" id="option-print_name" name="print_name" /> <label><?php printf( _x( 'Show the table name %s the table.', 'position (above or below)', 'tablepress' ), $position_select ); ?></label></td>
	</tr>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><label for="option-print_description"><?php _e( 'Print Table Description', 'tablepress' ); ?></label>:</th>
		<?php
			$position_select = '<select id="option-print_description_position" name="print_description_position">';
			$position_select .= '<option value="above">' . __( 'above', 'tablepress' ) . '</option>';
			$position_select .= '<option value="below">' . __( 'below', 'tablepress' ) . '</option>';
			$position_select .= '</select>';
		?>
		<td class="column-2"><input type="checkbox" id="option-print_description" name="print_description" /> <label><?php printf( _x( 'Show the table description %s the table.', 'position (above or below)', 'tablepress' ), $position_select ); ?></label></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Extra CSS Classes', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-extra_css_classes"><input type="text" id="option-extra_css_classes" name="extra_css_classes" class="large-text code" title="<?php esc_attr_e( 'This field can only contain letters, numbers, spaces, hyphens (-), underscores (_), and colons (:).', 'tablepress' ); ?>" pattern="[A-Za-z0-9- _:]*" /><p class="description"><?php echo __( 'Additional CSS classes for styling purposes can be entered here.', 'tablepress' ) . ' ' . sprintf( __( 'This is NOT the place to enter <a href="%s">Custom CSS</a> code!', 'tablepress' ), TablePress::url( array( 'action' => 'options' ) ) ); ?></p></label></td>
	</tr>
</table>
		<?php
	}

	/**
	 * Print the content of the "Table Features for Site Visitors" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the meta box.
	 */
	public function postbox_datatables_features( array $data, array $box ) {
		?>
<p id="notice-datatables-head-row"><em><?php printf( __( 'These features and options are only available when the &#8220;%1$s&#8221; checkbox in the &#8220;%2$s&#8221; section is checked.', 'tablepress' ), __( 'Table Head Row', 'tablepress' ), __( 'Table Options', 'tablepress' ) ); ?></em></p>
<table class="tablepress-postbox-table fixed">
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'Enable Visitor Features', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-use_datatables"><input type="checkbox" id="option-use_datatables" name="use_datatables" /> <?php _e( 'Offer the following functions for site visitors with this table:', 'tablepress' ); ?></label></td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Sorting', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables_sort"><input type="checkbox" id="option-datatables_sort" name="datatables_sort" /> <?php _e( 'Enable sorting of the table by the visitor.', 'tablepress' ); ?></label></td>
	</tr>
	<tr>
		<th class="column-1" scope="row"><?php _e( 'Search/Filtering', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables_filter"><input type="checkbox" id="option-datatables_filter" name="datatables_filter" /> <?php _e( 'Enable the visitor to filter or search the table. Only rows with the search word in them are shown.', 'tablepress' ); ?></label></td>
	</tr>
	<tr>
		<th class="column-1" scope="row" style="vertical-align: top;"><?php _e( 'Pagination', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables_paginate"><input type="checkbox" id="option-datatables_paginate" name="datatables_paginate" /> <?php _e( 'Enable pagination of the table (viewing only a certain number of rows at a time) by the visitor.', 'tablepress' ); ?></label><br />
		<label for="option-datatables_paginate_entries" class="checkbox-left">&nbsp;<?php printf( __( 'Show %s rows per page.', 'tablepress' ), '<input type="number" id="option-datatables_paginate_entries" class="small-text" name="datatables_paginate_entries" min="1" max="99999" required />' ); ?></label></td>
	</tr>
	<tr>
		<th class="column-1" scope="row"><?php _e( 'Pagination Length Change', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables_lengthchange"><input type="checkbox" id="option-datatables_lengthchange" name="datatables_lengthchange" /> <?php _e( 'Allow the visitor to change the number of rows shown when using pagination.', 'tablepress' ); ?></label></td>
	</tr>
	<tr>
		<th class="column-1" scope="row"><?php _e( 'Info', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables_info"><input type="checkbox" id="option-datatables_info" name="datatables_info" /> <?php _e( 'Enable the table information display, with information about the currently visible data, like the number of rows.', 'tablepress' ); ?></label></td>
	</tr>
	<tr<?php echo current_user_can( 'unfiltered_html' ) ? ' class="bottom-border"' : ''; ?>>
		<th class="column-1" scope="row"><?php _e( 'Horizontal Scrolling', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables_scrollx"><input type="checkbox" id="option-datatables_scrollx" name="datatables_scrollx" /> <?php _e( 'Enable horizontal scrolling, to make viewing tables with many columns easier.', 'tablepress' ); ?></label></td>
	</tr>
		<?php
		// "Custom Commands" must only be available to trusted users.
		if ( current_user_can( 'unfiltered_html' ) ) {
			?>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Custom Commands', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-datatables_custom_commands"><textarea id="option-datatables_custom_commands" name="datatables_custom_commands" class="large-text code" rows="1"></textarea><p class="description"><?php echo sprintf( __( 'Additional parameters from the <a href="%s">DataTables documentation</a> to be added to the JS call.', 'tablepress' ), 'https://www.datatables.net/' ) . ' ' . __( 'For advanced use only.', 'tablepress' ); ?></p></label></td>
	</tr>
			<?php
		} // if
		?>
</table>
		<?php
	}

	/**
	 * Print a notification about a corrupted table.
	 *
	 * @since 1.4.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the text box.
	 */
	public function textbox_corrupted_table( array $data, array $box ) {
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
				<?php echo '<a href="' . TablePress::url( array( 'action' => 'list' ) ) . '" class="button">' . __( 'Back to the List of Tables', 'tablepress' ) . '</a>'; ?>
			</p>
		</div>
		<?php
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
	public function wp_pointer_tp20_edit_context_menu() {
		$content  = '<h3>' . __( 'TablePress feature: Context menu', 'tablepress' ) . '</h3>';
		$content .= '<p>' . __( 'Did you know?', 'tablepress' ) . ' ' . __( 'Right-clicking the table content fields will open a context menu for quick access to common editing tools.', 'tablepress' ) . '</p>';

		$this->admin_page->print_wp_pointer_js(
			'tp20_edit_context_menu',
			'#table-editor',
			array(
				'content'  => $content,
				'position' => array( 'edge' => 'bottom', 'align' => 'center' ),
			)
		);
	}

	/**
	 * Sets the content for the WP feature pointer about the screen options for changing the cell size.
	 *
	 * @since 2.1.0
	 */
	public function wp_pointer_tp21_edit_screen_options() {
		$content  = '<h3>' . __( 'TablePress feature: Column width and row height of the table editor', 'tablepress' ) . '</h3>';
		$content .= '<p>' . __( 'Did you know?', 'tablepress' ) . ' ' . sprintf( __( 'You can change the default cell size for the table editor on this “Edit” screen in the “%s”.', 'tablepress' ), __( 'Screen Options', 'default' ) ) . '</p>';

		$this->admin_page->print_wp_pointer_js(
			'tp21_edit_screen_options',
			'#screen-options-link-wrap',
			array(
				'content'      => $content,
				'position'     => array( 'edge' => 'top', 'align' => 'right' ),
				'pointerClass' => 'wp-pointer pointer-tp21_edit_screen_options',
			)
		);
	}

} // class TablePress_Edit_View
