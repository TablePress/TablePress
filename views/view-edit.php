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

		add_thickbox();
		$this->admin_page->enqueue_style( 'edit' );
		$this->admin_page->enqueue_script( 'edit', array( 'jquery', 'jquery-ui-sortable', 'json2' ), array(
			'options' => array(
				'cells_advanced_editor' => true,
				'cells_auto_grow' => true
			),
			'strings' => array(
				'no_remove_all_rows' => 'Du kannst nicht alle Zeilen der Tabelle löschen!',
				'no_remove_all_columns' => 'Du kannst nicht alle Spalten der Tabelle löschen!',
				'no_rows_selected' => 'Du hast keine Zeilen ausgewählt!',
				'no_columns_selected' => 'Du hast keine Spalten ausgewählt!',
				'append_num_rows_invalid' => 'Die Eingabe für die Zeilenanzahl ist ungültig!',
				'append_num_columns_invalid' => 'Die Eingabe für die Spaltenanzahl ist ungültig!',
				'ays_remove_rows' => 'Möchtest du die ausgewählten Zeilen wirklich löschen?',
				'ays_remove_columns' => 'Möchtest du die ausgewählten Spalten wirklich löschen?',
				'advanced_editor_open' => 'Bitte klicke in die Zelle, die du bearbeiten möchtest.',
				'span_add' => 'Bitte klicke in die Zelle, die verbunden werden soll.',
				'link_add' => 'Bitte klicke in die Zelle, in die du einen Link einfügen möchtest.',
				'unsaved_changes_unload' => 'Seite ohne speichern verlassen?',
				'preview' => 'Vorschau',
				'preparing_preview' => 'Die Vorschau wird vorbereitet...',
				'preview_error' => 'Vorbereiten der Vorschau fehlgeschlagen',
				'save_changes_success' => 'Speichern erfolgreich',
				'save_changes_error' => 'Speichern fehlgeschlagen',
				'saving_changes' => 'Speichere Änderungen...',
				'ays_change_table_id' => 'Willst du die Tabellen-ID wirklich ändern? Alle Shortcodes für diese Tabelle müssen angepasst werden!',
				'sort_asc' => 'Aufsteigend sortieren',
				'sort_desc' => 'Absteigend sortieren',
				'no_rowspan_first_row' => 'You can not add rowspan to the first row!',
				'no_colspan_first_col' => 'You can not add colspan to the first column!'
			)
		) );

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
			$class = ( in_array( $data['message'], array( 'error_save', 'error_delete', 'success_save_error_id_change' ) ) ) ? 'error' : 'updated' ;
			$this->add_header_message( "<strong>{$this->action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		$this->add_meta_box( 'table-information', __( 'Table Information', 'tablepress' ), array( &$this, 'postbox_table_information' ), 'normal' );
		$this->add_text_box( 'buttons-1', array( &$this, 'textbox_buttons' ), 'normal' );
		$this->add_meta_box( 'table-data', __( 'Table Content', 'tablepress' ), array( &$this, 'postbox_table_data' ), 'normal' );
		$this->add_meta_box( 'table-manipulation', __( 'Table Manipulation', 'tablepress' ), array( &$this, 'postbox_table_manipulation' ), 'normal' );
		$this->add_meta_box( 'table-options', __( 'Table Options', 'tablepress' ), array( &$this, 'postbox_table_options' ), 'normal' );
		$this->add_text_box( 'hidden-containers', array( &$this, 'textbox_hidden_containers' ), 'additional' );
		$this->add_text_box( 'buttons-2', array( &$this, 'textbox_buttons' ), 'additional' );
		$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'submit' );
		$this->add_text_box( 'other-actions', array( &$this, 'textbox_other_actions' ), 'submit' );
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
		wp_nonce_field( TablePress::nonce( $this->action, $data['table']['id'] ) ); echo "\n";
		// check if necessary:
		wp_nonce_field( 'tp-save-table', 'tp-ajax-nonce-save-table', false, true );
		wp_nonce_field( 'tp-preview-table', 'tp-ajax-nonce-preview-table', false, true );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function postbox_table_information( $data, $box ) {
		?>
		<table class="form-table">
		<tbody>
		<tr valign="top">
			<th scope="row"><label for="table-id"><?php _e( 'Table ID', 'tablepress' ); ?>:</label></th>
			<td>
				<input type="hidden" name="orig_table_id" id="orig-table-id" value="<?php echo esc_attr( $data['table']['id'] ); ?>" />
				<input type="text" name="table[id]" id="table-id" class="small-text" value="<?php echo esc_attr( $data['table']['id'] ); ?>" />
				<input type="text" id="table-shortcode" value="[table id=<?php echo esc_attr( $data['table']['id'] ); ?> /]" readonly="readonly" /><br/>
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
			<td><?php printf( __( 'at %1$s by %2$s', 'tablepress' ), esc_html( $data['table']['options']['last_modified'] ), esc_html( $data['table']['options']['last_editor'] ) ); ?></td>
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
	function postbox_table_data( $data, $box ) {
		$table = array(
			array( '=[E1]',	'=5-4', '3', '=[C1]', '=[C1]+[D1]' ),
			array( '1',	'2', '3', '4', '5' ),
			array( '=2*([A2]+[B2]+[C2]+[D2]+[E2])', '=average([A2:E2],[A2:E2])', '=sum([A2:E2],[A2:E2])', '=    5 *   5', '=sin(pi()/2)' ),
			array( '=poWer(3,2)', '=3^2', '=2*[C4]', '=TAN(64)', '=ceil(PI())' ),
			array( '=[B4]', '=[A4]', '="[4]"', '=sqrt(77.3) / 99', '=[E4]' ),
			array( '=log(100)', '=round(4.533)', '=pi+e', '=[C14]', '=[E1]' ),
			array( '=rand_int(1,10)', '=average([B1],[C1],[D1])', '=sum([B1],[C1],[D1])', '=min([B1],[C1],[D1])', '=max([B1],[C1],[D1])' )
		);
		//$table = $data['table']['data'];
		$rows = count( $table );
		$columns = count( $table[0] );
?>
<table id="tp-edit">
	<thead>
		<tr id="tp-edit-head">
			<th></th>
			<th></th>
<?php			
	for ( $col_idx = 0; $col_idx < $columns; $col_idx++ ) {
		$column = TablePress::number_to_letter( $col_idx + 1 );
		echo "\t\t\t<th class=\"head\"><span class=\"sort-control sort-desc\" title=\"Absteigend sortieren\"></span><span class=\"sort-control sort-asc\" title=\"Aufsteigend sortieren\"></span><span class=\"move-handle\">{$column}</span></th>\n";
	}
?>
			<th></th>			
		</tr>	
	</thead>
	<tfoot>
		<tr id="tp-edit-foot">
			<th></th>
			<th></th>
<?php			
	for ( $col_idx = 0; $col_idx < $columns; $col_idx++ ) {
		echo "\t\t\t<th><input type=\"checkbox\" />";
		echo "<input type=\"hidden\" class=\"visibility\" name=\"tp[visibility][column][{$col_idx}]\" value=\"1\" /></th>\n";
	}
?>
			<th></th>			
		</tr>	
	</tfoot>
	<tbody id="tp-edit-body">
<?php
	foreach ( $table as $row_idx => $row_data ) {
		$row = $row_idx + 1;
		$classes = array();
		if ( $row_idx % 2 == 0 )
			$classes[] = 'odd';
		if ( 0 == $row_idx )
			$classes[] = 'head-row';
		elseif ( ( $rows - 1 ) == $row_idx )
			$classes[] = 'foot-row';
		$class = ( ! empty( $classes ) ) ? ' class="' . implode( ' ', $classes ) . '"' : '';
		echo "\t\t<tr{$class}>\n";
		echo "\t\t\t<td><span class=\"move-handle\">{$row}</span></td>";
		echo "<td><input type=\"checkbox\" /><input type=\"hidden\" class=\"visibility\" name=\"tp[visibility][row][{$row_idx}]\" value=\"1\" /></td>";
		foreach ( $row_data as $col_idx => $cell ) {
			$column = TablePress::number_to_letter( $col_idx + 1 );
			echo "<td><textarea name=\"tp[data][{$row_idx}][{$col_idx}]\" id=\"tp-cell-{$column}{$row}\" rows=\"1\">{$cell}</textarea></td>";
		}	
		echo "<td><span class=\"move-handle\">{$row}</span></td>\n";
		echo "\t\t</tr>\n";
	}
?>
	</tbody>
</table>
<input type="hidden" id="tp-rows" value="<?php echo $rows; ?>" />
<input type="hidden" id="tp-columns" value="<?php echo $columns; ?>" />
<?php
	}
	
	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function postbox_table_manipulation( $data, $box ) {
?>
<table class="tablepress-postbox-table">
<tbody>
	<tr class="bottom-border">
		<td>
			<input type="button" class="button-secondary" id="tp-link-add" value="<?php _e( 'Insert Link', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="tp-image-add" value="<?php _e( 'Insert Image', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="advanced-editor-open" value="<?php _e( 'Advanced Editor', 'tablepress' ); ?>" />
		</td>
		<td>
			<?php _e( 'Combine cells', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button-secondary" id="tp-span-add-rowspan" value="<?php _e( 'rowspan', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="tp-span-add-colspan" value="<?php _e( 'colspan', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="top-border">
		<td>
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button-secondary" id="tp-rows-hide" value="<?php _e( 'Hide', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="tp-rows-unhide" value="<?php _e( 'Show', 'tablepress' ); ?>" />
		</td>
		<td>
			<?php _e( 'Selected columns', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button-secondary" id="tp-columns-hide" value="<?php _e( 'Hide', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="tp-columns-unhide" value="<?php _e( 'Show', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="bottom-border">
		<td>
			<?php _e( 'Selected rows', 'tablepress' ); ?>:&nbsp;
			<input type="button" class="button-secondary" id="tp-rows-insert" value="<?php _e( 'Insert', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="tp-rows-remove" value="<?php _e( 'Delete', 'tablepress' ); ?>" />
		</td>
		<td>
			<?php _e( 'Selected columns', 'tablepress' ); ?>:
			<input type="button" class="button-secondary" id="tp-columns-insert" value="<?php _e( 'Insert', 'tablepress' ); ?>" />
			<input type="button" class="button-secondary" id="tp-columns-remove" value="<?php _e( 'Delete', 'tablepress' ); ?>" />
		</td>
	</tr>
	<tr class="top-border">
		<td>
			<?php printf( __( 'Add %s row(s)', 'tablepress' ), '<input type="text" id="tp-rows-append-num" class="small-text numbers-only" value="1" maxlength="5" />' ); ?>&nbsp;<input type="button" class="button-secondary" id="tp-rows-append" value="<?php _e( 'Add', 'tablepress' ); ?>" />
		</td>
		<td>
			<?php printf( __( 'Add %s column(s)', 'tablepress' ), '<input type="text" id="tp-columns-append-num" class="small-text numbers-only" value="1" maxlength="5" />' ); ?>&nbsp;<input type="button" class="button-secondary" id="tp-columns-append" value="<?php _e( 'Add', 'tablepress' ); ?>" />
		</td>
	</tr>
</table>
<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function textbox_buttons( $data, $box ) {
		?>
			<p class="submit">
				<input type="button" class="button-secondary tp-show-preview" value="<?php _e( 'Preview', 'tablepress' ); ?>" /><br/>
				<input type="button" class="button-secondary tp-save-changes" value="<?php _e( 'Save Changes', 'tablepress' ); ?>" />
			</p>
		<?php
	}
	
	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function textbox_other_actions( $data, $box ) {
		?>
			<p class="submit">
				<?php _e( 'Other Actions' ); ?>:&nbsp;
				<a href="<?php echo TablePress::url( array( 'action' => 'delete_table', 'item' => $data['table']['id'], 'return' => 'edit', 'return_item' => $data['table']['id'] ), true, 'admin-post.php' ); ?>" class="button-secondary"><?php _e( 'Delete Table', 'tablepress' ); ?></a>
				<?php /* @TODO: Add Export button here */ ?>
			</p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function textbox_hidden_containers( $data, $box ) {
?>
<div id="advanced-editor-container" class="tp-hidden-container">
	<div id="advanced-editor"><?php _e( 'Advanced Editor', 'tablepress' ); ?><br/>
	<?php
		wp_editor( '', 'advanced-editor-content', array(
			'media_buttons' => false,
			'textarea_rows' => 10,
			'tinymce' => false,
			'quicktags' => array(
				'buttons' => 'strong,em,link,del,ins,img,code,spell,close'
			)
		) );
	?>
	<input type="button" class="button-secondary advanced-editor-button" id="advanced-editor-confirm" value="<?php _e( 'OK', 'tablepress' ); ?>" /><input type="button" class="button-secondary advanced-editor-button" id="advanced-editor-cancel" value="<?php _e( 'Cancel', 'tablepress' ); ?>" />
	</div>
</div>
<div id="tp-preview-container" class="tp-hidden-container">
	<div id="tp-preview"></div>
</div>
<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	function postbox_table_options( $data, $box ) {
?>
<table class="tablepress-postbox-table">
<tbody>
	<tr>
		<td class="column-1"><label for="tp-table-head"><?php _e( 'Tabellenkopf', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="tp-table-head" checked="checked" /></td>
	</tr>
	<tr class="bottom-border">
		<td class="column-1"><label for="tp-table-foot"><?php _e( 'Tabellenfuß', 'tablepress' ); ?>:</label></td>
		<td class="column-2"><input type="checkbox" id="tp-table-foot" checked="checked" /></td>
	</tr>
	<tr class="top-border">
		<td colspan="2"><?php echo json_encode( $data['table']['options'] ); ?></td>
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
	function textbox_head( $data, $box ) {
		?>
		<p><?php _e( 'On this page, you can edit the content of the table.', 'tablepress' ); ?> <?php _e( 'It is also possible to change the table structure by inserting, deleting, moving, and swapping columns and rows.', 'tablepress' ); ?><br />
		<?php printf( __( 'To insert the table into a page, post or text-widget, copy the shortcode <strong>[table id=%s /]</strong> and paste it into the corresponding place in the editor.', 'tablepress' ), esc_html( $data['table']['id'] ) ); ?></p>
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