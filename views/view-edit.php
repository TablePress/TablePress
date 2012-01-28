<?php
/**
 * Edit Table View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Edit Table View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Edit_View extends TablePress_View {

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
			'success_save' => __( 'The table was saved successfully.', 'tablepress' ),
			'success_add' => __( 'The table was added successfully.', 'tablepress' ),
		/*	'success_import' => __( 'The table was imported successfully.', 'tablepress' ), */
			'error_save' => __( 'Error: The table could not be saved.', 'tablepress' ),
			'error_delete' => __( 'Error: The table could not be deleted.', 'tablepress' ),
			'success_save_success_id_change' => __( 'The table was saved successfully, and the table ID was changed.', 'tablepress' ),
			'success_save_error_id_change' => __( 'The table was saved successfully, but the table ID could not be changed!', 'tablepress' )
		);

		if ( $data['message'] && isset( $this->action_messages[ $data['message'] ] ) ) {
			$class = ( 'error' == substr( $data['message'], 0, 5 ) || in_array( $data['message'], array( 'success_save_error_id_change' ) ) ) ? 'error' : 'updated' ;
			$this->add_header_message( "<strong>{$this->action_messages[ $data['message'] ]}</strong>", $class );
		}

		// do this here to get CSS into <head>
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		add_action( 'admin_footer', array( &$this, 'dequeue_media_upload_js'), 2 );
		add_thickbox();
		$this->admin_page->enqueue_style( 'edit' );
		$this->admin_page->enqueue_script( 'edit', array( 'jquery', 'jquery-ui-sortable', 'json2' ), array(
			'options' => array(
				'cells_advanced_editor' => true,
				'cells_auto_grow' => true,
				'shortcode' => TablePress::$shortcode
			),
			'strings' => array_merge( array(
				'no_remove_all_rows' => 'Du kannst nicht alle Zeilen der Tabelle löschen!',
				'no_remove_all_columns' => 'Du kannst nicht alle Spalten der Tabelle löschen!',
				'no_rows_selected' => 'Du hast keine Zeilen ausgewählt!',
				'no_columns_selected' => 'Du hast keine Spalten ausgewählt!',
				'append_num_rows_invalid' => 'Die Eingabe für die Zeilenanzahl ist ungültig!',
				'append_num_columns_invalid' => 'Die Eingabe für die Spaltenanzahl ist ungültig!',
				'ays_remove_rows_singular' => _n( 'Do you really want to delete this row?', 'Do you really want to delete these rows?', 1, 'tablepress' ),
				'ays_remove_rows_plural' => _n( 'Do you really want to delete this row?', 'Do you really want to delete these rows?', 2, 'tablepress' ),
				'ays_remove_columns_singular' => _n( 'Do you really want to delete this column?', 'Do you really want to delete these columns?', 1, 'tablepress' ),
				'ays_remove_columns_plural' => _n( 'Do you really want to delete this column?', 'Do you really want to delete these columns?', 2, 'tablepress' ),
				'advanced_editor_open' => 'Bitte klicke in die Zelle, die du bearbeiten möchtest.',
				'span_add' => 'Bitte klicke in die Zelle, die verbunden werden soll.',
				'link_add' => 'Bitte klicke in die Zelle, in die du einen Link einfügen möchtest.',
				'image_add' => 'Bitte klicke in die Zelle, in die du ein Bild einfügen möchtest.' . "\n" .
								__( 'The Media Library will open, from which you can select the desired image or insert the image URL.', 'tablepress' ) . "\n" .
								sprintf( __( 'Click the &quot;%s&quot; button to insert the image.', 'tablepress' ), __( 'Insert into Post', 'default' ) ) ,				
				'unsaved_changes_unload' => 'Seite ohne speichern verlassen?',
				'preparing_preview' => 'Die Vorschau wird vorbereitet...',
				'preview_error' => 'Vorbereiten der Vorschau fehlgeschlagen',
				'save_changes_success' => 'Speichern erfolgreich',
				'save_changes_error' => 'Speichern fehlgeschlagen',
				'saving_changes' => 'Speichere Änderungen...',
				'table_id_not_empty' => __( 'The Table ID field can not be empty. Please enter a Table ID!', 'tablepress' ),
				'ays_change_table_id' => 'Willst du die Tabellen-ID wirklich ändern? Alle Shortcodes für diese Tabelle müssen angepasst werden!',
				'extra_css_classes_invalid' => __( 'The entered value in the field &quot;Extra CSS classes&quot; is invalid.', 'tablepress' ),
				'sort_asc' => __( 'Sort ascending', 'tablepress' ),
				'sort_desc' => __( 'Sort descending', 'tablepress' ),
				'no_rowspan_first_row' => 'You can not add rowspan to the first row!',
				'no_colspan_first_col' => 'You can not add colspan to the first column!',
				'no_rowspan_table_head' => 'You can not add rowspan into the table head row!',
				'no_rowspan_table_foot' => 'You can not add rowspan out of the table foot row!'
			), $this->action_messages ) // merge this to have messages available for AJAX after save dialog
		) );

		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		$this->add_meta_box( 'table-information', __( 'Table Information', 'tablepress' ), array( &$this, 'postbox_table_information' ), 'normal' );
		$this->add_text_box( 'buttons-1', array( &$this, 'textbox_buttons' ), 'normal' );
		$this->add_meta_box( 'table-data', __( 'Table Content', 'tablepress' ), array( &$this, 'postbox_table_data' ), 'normal' );
		$this->add_meta_box( 'table-manipulation', __( 'Table Manipulation', 'tablepress' ), array( &$this, 'postbox_table_manipulation' ), 'normal' );
		$this->add_meta_box( 'table-options', __( 'Table Options', 'tablepress' ), array( &$this, 'postbox_table_options' ), 'normal' );
		$this->add_meta_box( 'datatables-features', __( 'Features of the DataTables JavaScript library', 'tablepress' ), array( &$this, 'postbox_datatables_features' ), 'normal' );
		$this->add_text_box( 'hidden-containers', array( &$this, 'textbox_hidden_containers' ), 'additional' );
		$this->add_text_box( 'buttons-2', array( &$this, 'textbox_buttons' ), 'additional' );
		$this->add_text_box( 'other-actions', array( &$this, 'textbox_other_actions' ), 'submit' );
	}

	/**
	 * Dequeue 'media-upload' JavaScript, which gets added by the Media Library,
	 * but is undesired here, as we have a custom function for this (send_to_editor()) and
	 * don't want the tb_position() function for resizing
	 *
	 * @since 1.0.0
	 */
	public function dequeue_media_upload_js() {
		wp_dequeue_script( 'media-upload' );
	}

	/**
	 * Print hidden field with a nonce for the screen's action, to be transmitted in HTTP requests
	 *
	 * @since 1.0.0
	 * @uses wp_nonce_field()
	 *
	 * @param array $data Data for this screen
	 * @param array $box Information about the text box
	 */
	protected function action_nonce_field( $data, $box ) {
		// use custom nonce field here, that includes the table ID
		wp_nonce_field( TablePress::nonce( $this->action, $data['table']['id'] ), 'nonce-edit-table' ); echo "\n";
		wp_nonce_field( TablePress::nonce( 'preview_table', $data['table']['id'] ), 'nonce-preview-table', false, true );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_table_information( $data, $box ) {
		?>
		<table class="form-table">
		<tbody>
		<tr valign="top">
			<th scope="row"><label for="table-id"><?php _e( 'Table ID', 'tablepress' ); ?>:</label></th>
			<td>
				<input type="hidden" name="table[id]" id="table-id" value="<?php echo esc_attr( $data['table']['id'] ); ?>" />
				<input type="text" name="table[new_id]" id="table-new-id" class="small-text" value="<?php echo esc_attr( $data['table']['id'] ); ?>" title="<?php _e( 'The Table ID can only consist of letters, numbers, hyphens (-), and underscores (_).', 'tablepress' ); ?>" pattern="[A-Za-z0-9-_]+" required />
				<input type="text" class="table-shortcode" value="[<?php echo TablePress::$shortcode; ?> id=<?php echo esc_attr( $data['table']['id'] ); ?> /]" readonly="readonly" /><br/>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="table-name"><?php _e( 'Table Name', 'tablepress' ); ?>:</label></th>
			<td><input type="text" name="table[name]" id="table-name" class="large-text" value="<?php echo esc_attr( $data['table']['name'] ); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="table-description"><?php _e( 'Description', 'tablepress' ); ?>:</label></th>
			<td><textarea name="table[description]" id="table-description" class="large-text" rows="4"><?php echo esc_textarea( $data['table']['description'] ); ?></textarea></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e( 'Last Modified', 'tablepress' ); ?>:</th>
			<td><?php printf( __( '%1$s by %2$s', 'tablepress' ), '<span id="last-modified">' . TablePress::format_datetime( $data['table']['last_modified'] ) . '</span>', '<span id="last-editor">' . TablePress::get_user_display_name( $data['table']['options']['last_editor'] ) . '</span>' ); ?></td>
		</tr>
		</tbody>
		</table>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_table_data( $data, $box ) {
		$table = $data['table']['data'];
		$options = $data['table']['options'];
		$visibility = $data['table']['visibility'];
		$rows = count( $table );
		$columns = count( $table[0] );

		$head_row_idx = $foot_row_idx = -1;
		// determine row index of the table head row, by excluding all hidden rows from the beginning
		if ( $options['table_head'] ) {
			for ( $row_idx = 0; $row_idx < $rows; $row_idx++ ) {
				if ( 1 === $visibility['rows'][$row_idx] ) {
					$head_row_idx = $row_idx;
					break;
				}
			}
		}
		// determine row index of the table foot row, by excluding all hidden rows from the end
		if ( $options['table_foot'] ) {
			for ( $row_idx = $rows - 1; $row_idx > -1; $row_idx-- ) {
				if ( 1 === $visibility['rows'][$row_idx] ) {
					$foot_row_idx = $row_idx;
					break;
				}
			}
		}
?>
<table id="edit-form">
	<thead>
		<tr id="edit-form-head">
			<th></th>
			<th></th>
<?php
	for ( $col_idx = 0; $col_idx < $columns; $col_idx++ ) {
		$column_class = '';
		if ( 0 === $visibility['columns'][$col_idx] )
			$column_class = ' column-hidden';
		$column = TablePress::number_to_letter( $col_idx + 1 );
		echo "\t\t\t<th class=\"head{$column_class}\"><span class=\"sort-control sort-desc\" title=\"" . __( 'Sort descending', 'tablepress' ) . "\"></span><span class=\"sort-control sort-asc\" title=\"" . __( 'Sort ascending', 'tablepress' ) . "\"></span><span class=\"move-handle\">{$column}</span></th>\n";
	}
?>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<tr id="edit-form-foot">
			<th></th>
			<th></th>
<?php
	for ( $col_idx = 0; $col_idx < $columns; $col_idx++ ) {
		$column_class = '';
		if ( 0 === $visibility['columns'][$col_idx] )
			$column_class = ' class="column-hidden"';
		echo "\t\t\t<th{$column_class}><input type=\"checkbox\" class=\"hide-if-no-js\" />";
		echo "<input type=\"hidden\" class=\"visibility\" name=\"table[visibility][columns][]\" value=\"{$visibility['columns'][$col_idx]}\" /></th>\n";
	}
?>
			<th></th>
		</tr>
	</tfoot>
	<tbody id="edit-form-body">
<?php
	foreach ( $table as $row_idx => $row_data ) {
		$row = $row_idx + 1;
		$classes = array();
		if ( $row_idx % 2 == 0 )
			$classes[] = 'odd';
		if ( $head_row_idx == $row_idx )
			$classes[] = 'head-row';
		elseif ( $foot_row_idx == $row_idx )
			$classes[] = 'foot-row';
		if ( 0 === $visibility['rows'][$row_idx] )
			$classes[] = 'row-hidden';
		$row_class = ( ! empty( $classes ) ) ? ' class="' . implode( ' ', $classes ) . '"' : '';
		echo "\t\t<tr{$row_class}>\n";
		echo "\t\t\t<td><span class=\"move-handle\">{$row}</span></td>";
		echo "<td><input type=\"checkbox\" class=\"hide-if-no-js\" /><input type=\"hidden\" class=\"visibility\" name=\"table[visibility][rows][]\" value=\"{$visibility['rows'][$row_idx]}\" /></td>";
		foreach ( $row_data as $col_idx => $cell ) {
			$column = TablePress::number_to_letter( $col_idx + 1 );
			$column_class = '';
			if ( 0 === $visibility['columns'][$col_idx] )
				$column_class = ' class="column-hidden"';
			$cell = esc_textarea( $cell ); // sanitize, so that HTML is possible in table cells
			echo "<td{$column_class}><textarea name=\"table[data][{$row_idx}][{$col_idx}]\" id=\"cell-{$column}{$row}\" rows=\"1\">{$cell}</textarea></td>";
		}
		echo "<td><span class=\"move-handle\">{$row}</span></td>\n";
		echo "\t\t</tr>\n";
	}
?>
	</tbody>
</table>
<input type="hidden" id="number-rows" name="table[number][rows]" value="<?php echo $rows; ?>" />
<input type="hidden" id="number-columns" name="table[number][columns]" value="<?php echo $columns; ?>" />
<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_table_manipulation( $data, $box ) {
		$media_library_url = esc_url( add_query_arg( array( 'post_id' => '0', 'type' => 'image', 'tab' => 'library'), admin_url( 'media-upload.php' ) ) );
?>
<table class="tablepress-postbox-table hide-if-no-js">
<tbody>
	<tr class="bottom-border">
		<td>
			<input type="button" class="button-secondary" id="link-add" value="<?php _e( 'Insert Link', 'tablepress' ); ?>" />
			<a href="<?php echo $media_library_url; ?>" class="button-secondary" id="image-add"><?php _e( 'Insert Image', 'tablepress' ); ?></a>
			<input type="button" class="button-secondary" id="advanced-editor-open" value="<?php _e( 'Advanced Editor', 'tablepress' ); ?>" />
		</td>
		<td>
			<?php _e( 'Combine cells', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button-secondary" id="span-add-rowspan" value="<?php _e( 'rowspan', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="span-add-colspan" value="<?php _e( 'colspan', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="top-border">
		<td>
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button-secondary" id="rows-hide" value="<?php _e( 'Hide', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="rows-unhide" value="<?php _e( 'Show', 'tablepress' ); ?>" />
		</td>
		<td>
			<?php _e( 'Selected columns', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button-secondary" id="columns-hide" value="<?php _e( 'Hide', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="columns-unhide" value="<?php _e( 'Show', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="bottom-border">
		<td>
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button-secondary" id="rows-insert" value="<?php _e( 'Insert', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="rows-remove" value="<?php _e( 'Delete', 'tablepress' ); ?>" />
		</td>
		<td>
			<?php _e( 'Selected columns', 'tablepress' ); ?>:
			<input type="button" class="button-secondary" id="columns-insert" value="<?php _e( 'Insert', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="columns-remove" value="<?php _e( 'Delete', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="top-border">
		<td>
			<?php printf( __( 'Add %s row(s)', 'tablepress' ), '<input type="number" id="rows-append-number" class="small-text numbers-only" title="' . __( 'This field must contain a positive number.', 'tablepress' ) . '" value="1" min="1" max="99999" maxlength="5" required />' ); ?>&nbsp;<input type="button" class="button-secondary" id="rows-append" value="<?php _e( 'Add', 'tablepress' ); ?>" />
		</td>
		<td>
			<?php printf( __( 'Add %s column(s)', 'tablepress' ), '<input type="number" id="columns-append-number" class="small-text numbers-only" title="' . __( 'This field must contain a positive number.', 'tablepress' ) . '" value="1" min="1" max="99999" maxlength="5" required />' ); ?>&nbsp;<input type="button" class="button-secondary" id="columns-append" value="<?php _e( 'Add', 'tablepress' ); ?>" />
		</td>
	</tr>
</table>
<p class="hide-if-js"><?php _e( 'To use the Table Manipulation features, JavaScript needs to be enabled in your browser.', 'tablepress' ); ?></p>
<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_buttons( $data, $box ) {
		$preview_url = TablePress::url( array( 'action' => 'preview_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' );
		?>
			<p class="submit">
				<a href="<?php echo $preview_url; ?>" class="button-secondary show-preview-button" target="_blank"><?php _e( 'Preview', 'tablepress' ); ?></a>
				<input type="button" class="button-primary save-changes-button hide-if-no-js" value="<?php _e( 'Save Changes', 'tablepress' ); ?>" />
				<input type="submit" class="button-primary hide-if-js" value="<?php _e( 'Save Changes', 'tablepress' ); ?>" />
			</p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_other_actions( $data, $box ) {
		?>
			<p class="submit">
				<?php _e( 'Other Actions' ); ?>:&nbsp;
				<a href="<?php echo TablePress::url( array( 'action' => 'delete_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' ); ?>" class="button-secondary delete-link"><?php _e( 'Delete Table', 'tablepress' ); ?></a>
				<?php /* @TODO: Add Export button here */ ?>
			</p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_hidden_containers( $data, $box ) {
?>
<div class="hidden-container">
	<div id="advanced-editor">
	<?php
		wp_editor( '', 'advanced-editor-content', array(
			'textarea_rows' => 10,
			'tinymce' => false,
			'quicktags' => array(
				'buttons' => 'strong,em,link,del,ins,img,code,spell,close'
			)
		) );
	?>
	<div class="submitbox">
		<a href="#" class="submitdelete" id="advanced-editor-cancel"><?php _e( 'Cancel', 'tablepress' ); ?></a>
		<input type="button" class="button-primary" id="advanced-editor-confirm" value="<?php _e( 'OK', 'tablepress' ); ?>" />
	</div>
	</div>
</div>
<div id="preview-container" class="hidden-container">
	<div id="table-preview"></div>
</div>
<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_table_options( $data, $box ) {
		$options = $data['table']['options'];
?>
<table class="tablepress-postbox-table fixed">
<tbody>
	<tr>
		<td class="column-1"><label for="option-table-head"><?php _e( 'Table Head Row', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="option-table-head" name="table[options][table_head]" value="true"<?php checked( $options['table_head'] ); ?> /></td>
	</tr>
	<tr class="bottom-border">
		<td class="column-1"><label for="option-table-foot"><?php _e( 'Table Foot Row', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="option-table-foot" name="table[options][table_foot]" value="true"<?php checked( $options['table_foot'] ); ?> /></td>
	</tr>
	<tr class="top-border">
		<td class="column-1"><label for="option-alternating-row-colors"><?php _e( 'Alternating Row Colors', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="option-alternating-row-colors" name="table[options][alternating_row_colors]" value="true"<?php checked( $options['alternating_row_colors'] ); ?> /></td>
	</tr>
	<tr class="bottom-border">
		<td class="column-1"><label for="option-row-hover"><?php _e( 'Row Hover Highlighting', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="option-row-hover" name="table[options][row_hover]" value="true"<?php checked( $options['row_hover'] ); ?> /></td>
	</tr>
	<tr class="top-border">
		<td class="column-1"><label for="option-print-name"><?php _e( 'Print Table Name', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><select id="option-print-name" name="table[options][print_name]">
			<option<?php selected( 'no', $options['print_name'] ); ?> value="no"><?php _e( 'No', 'tablepress' ); ?></option>
			<option<?php selected( 'above', $options['print_name'] ); ?> value="above"><?php _e( 'Above', 'tablepress' ); ?></option>
			<option<?php selected( 'below', $options['print_name'] ); ?> value="below"><?php _e( 'Below', 'tablepress' ); ?></option>
		</select></td>
	</tr>
	<tr class="bottom-border">
		<td class="column-1"><label for="option-print-description"><?php _e( 'Print Table Description', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><select id="option-print-description" name="table[options][print_description]">
			<option<?php selected( 'no', $options['print_description'] ); ?> value="no"><?php _e( 'No', 'tablepress' ); ?></option>
			<option<?php selected( 'above', $options['print_description'] ); ?> value="above"><?php _e( 'Above', 'tablepress' ); ?></option>
			<option<?php selected( 'below', $options['print_description'] ); ?> value="below"><?php _e( 'Below', 'tablepress' ); ?></option>
		</select></td>
	</tr>
	<tr class="top-border bottom-border">
		<td class="column-1"><label for="option-extra-css-classes"><?php _e( 'Extra CSS Classes', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="text" id="option-extra-css-classes" class="large-text" name="table[options][extra_css_classes]" value="<?php echo esc_attr( $options['extra_css_classes'] ); ?>" title="<?php _e( 'This field can only contain letters, numbers, spaces, hyphens (-), and underscores (_).', 'tablepress' ); ?>" pattern="[A-Za-z0-9- _]*" /></td>
	</tr>
	<tr class="top-border bottom-border">
		<td colspan="2" style="width: 800px;"><?php echo json_encode( $options ); ?></td>
	</tr>
	<tr class="top-border">
		<td colspan="2"><?php echo json_encode( $data['table']['visibility'] ); ?></td>
	</tr>
</tbody>
</table>
<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_datatables_features( $data, $box ) {
		$options = $data['table']['options'];
?>
<p id="notice-datatables-head-row" class="hide-if-js"><?php printf( __( 'These features and options are only available, when the &quot;%1$s&quot; checkbox in the &quot;%2$s&quot; section is checked.', 'tablepress' ), __( 'Table Head Row', 'tablepress' ), __( 'Table Options', 'tablepress' ) ); ?></p>
<table class="tablepress-postbox-table fixed">
<tbody>
	<tr class="bottom-border">
		<td class="column-1"><label for="option-use-datatables"><?php _e( 'Use DataTables', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="option-use-datatables" name="table[options][use_datatables]" value="true"<?php checked( $options['use_datatables'] ); ?> /></td>
	</tr>
	<tr class="top-border">
		<td class="column-1"><label for="option-datatables-sort"><?php _e( 'Sorting', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="option-datatables-sorting" name="table[options][datatables_sort]" value="true"<?php checked( $options['datatables_sort'] ); ?> /></td>
	</tr>
	<tr class="bottom-border">
		<td class="column-1"><label for="option-datatables-filter"><?php _e( 'Search/Filtering', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="option-datatables-filter" name="table[options][datatables_filter]" value="true"<?php checked( $options['datatables_filter'] ); ?> /></td>
	</tr>
	<tr class="top-border">
		<td class="column-1"><label for="option-datatables-custom-commands"><?php _e( 'Custom Commands', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="text" id="option-datatables-custom-commands" class="large-text" name="table[options][datatables_custom_commands]" value="<?php echo esc_attr( $options['datatables_custom_commands'] ); ?>" /></td>
	</tr>
</tbody>
</table>
<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_head( $data, $box ) {
		?>
		<p>
		<?php _e( 'On this page, you can edit the content of the table.', 'tablepress' ); ?> <?php _e( 'It is also possible to change the table structure by inserting, deleting, moving, and swapping columns and rows.', 'tablepress' ); ?><br />
		<?php printf( __( 'To insert the table into a page, post or text-widget, copy the shortcode %s and paste it into the corresponding place in the editor.', 'tablepress' ), '<input type="text" class="table-shortcode table-shortcode-inline" value="[' . TablePress::$shortcode . ' id=' . esc_attr( $data['table']['id'] ) . ' /]" readonly="readonly" />' );?>
		</p>
		<?php
	}

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the Edit Table screen';
	}

} // class TablePress_Edit_View